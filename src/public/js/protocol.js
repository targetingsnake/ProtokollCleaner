(function(){
	$(document).ready(function(){
		$('.protolist .proto button').click(function(e){
			$e = $(this);
			var proto = $e.parent().prev().text();
			var perm = 'stura';
			window.location.href = '/protoedit?committee='+perm+'&proto='+proto;
		});
		
		$('.protostatus .legislatur button.add').click(function(e){
			$e = $(this);
			var proto_nr = parseInt($e.prev().text());
			proto_nr++;
			$e.prev().text(proto_nr);
		});
		
		$('.protostatus .legislatur button.sub').click(function(e){
			$e = $(this);
			var proto_nr = parseInt($e.next().text());
			proto_nr = Math.max(proto_nr-1, 1);
			$e.next().text(proto_nr);
		});
		
		var handleCommit = function ($e, state){
			if (state == 0){
				// show warning if there are any errors
				if ($('.difftable .line.error').length > 0){
					$.modaltools({
						headerClass: 'bg-warning',
						text: 'Es sind noch <strong>kritische Fehler</strong> vorhanden. Bitte bearbeiten Sie das Protokoll und entfernen Sie die Fehler um fortfahren zu können.', 
						buttons: {'ok':'Verstanden'}}).open();
				} else {
					handleCommit($e, state+1);
				}
				return;
			} else if (state == 1){
				// show warning if there are any errors
				if ($('.error.parseerrors .perror').length > 0){
					$.modaltools({
						headerClass: 'bg-warning',
						text: 'Es sind noch Fehler vorhanden. Soll wirklich fortgefahren werden?', 
						single_callback: function(key, obj){
							if (key == 'ok') setTimeout(function(){handleCommit($e, state+1);}, 350);
						}
					}).open();
				} else {
					handleCommit($e, state+1);
				}
				return;
			} else if(state == 2){
				// test if is no draft -> show warning if there are open fixme, todo, and deleteme
				if ($e.text().indexOf('Entwurf') !== -1){
					var todo = parseInt($('.protostatus .todo span').eq(1).text());
					var fix = parseInt($('.protostatus .fixme span').eq(1).text());
					var del = parseInt($('.protostatus .deleteme span').eq(1).text());
					if (todo > 0 || fix > 0 || del > 0){
						var text = '';
						if (todo > 0) text += '<strong>Todo</strong>';
						if (fix > 0) text += ((text!='')?', ':'')+ '<strong>FixMe</strong>';
						if (del > 0) text += ((text!='')?', ':'')+ '<strong>DeleteMe</strong>';
						$.modaltools({
							headerClass: 'bg-warning',
							text: 'Es sind noch offene '+text+' vorhanden. Soll wirklich fortgefahren werden?', 
							single_callback: function(key, obj){
								if (key == 'ok') setTimeout(function(){handleCommit($e, state+1);}, 350);
							}
						}).open();
					} else {
						handleCommit($e, state+1);
					}
				} else {
					handleCommit($e, state+1);
				}
				return;
			} else if (state == 3){
				// get/check attachements
				var attachements = [];
				$('.attachlist .attachementlist .line input:checked + label span').each(function(i, e){
					attachements.push(e.innerText);
				});
				// get variables
				var dataset = {
					period: $('.protostatus .legislatur > span > span').text(),
					attach: attachements,
					proto: $('.protostatus .date > span').last().data('name'),
					committee: $('.protostatus .committee > span').last().text(),
				};
				fchal = document.getElementById('fchal');
				dataset[fchal.getAttribute("name")] = fchal.value;
				//show info
				var modal = $.modaltools({
					text: '<strong>Anfrage wird verarbeitet. Bitte warten.</strong></p><p><div class="multifa center"><span class="fa fa-cog sym-spin"></span><span class="fa fa-cog sym-spin-reverse"></span></div>', 
					buttons: {}
				}).open();
				// do ajax post
				$.ajax({
					type: "POST",
					url: 'protocol/publish',
					data: dataset,
					success: function(data){
						modal.close();
						console.log(data);
						pdata = {};
						try {
							pdata = JSON.parse(data);
						} catch(e) {
							console.log(data);
							pdata.success=false;
							pdata.eMsg = ('Unerwarteter Fehler (Code: "'+data.status+'"). Seite wird neu geladen...');
							auto_page_reload(5000);
						}
						if(pdata.success == true){
							silmph__add_message(pdata.msg, MESSAGE_TYPE_SUCCESS, 3000);
							setTimeout(function(){
								window.location.href = '/protolist';
							}, 3000);
						} else {
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						}
					},
					error: function(data){
						modal.close();
						console.log(data);
						try {
							pdata = JSON.parse(data.responseText);
							silmph__add_message(pdata.eMsg, MESSAGE_TYPE_WARNING, 5000);
						} catch(e) {
							console.log(data);
							silmph__add_message('Unerwarteter Fehler (Code: "'+data.status+'"). Seite wird neu geladen...', MESSAGE_TYPE_WARNING, 5000);
							auto_page_reload(5000);
						}
					}
				});
				return;
			}
		}
		
		$('.protolinks button.commit').click(function(e){
			$e = $(this);
			handleCommit($e, 0);
		});
		
	});
	
})();