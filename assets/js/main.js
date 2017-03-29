jQuery(document).ready(function($){
	var fpn_buttons = $('.fpn_button');
	fpn_buttons.on('click', function(e){
		e.preventDefault();
		var post_id = $(this).data('post-id');
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data:{
				action: 'firebase_push_notification',
				post_id: post_id
			},
			success: function(response){
				alert('success!');
				console.log(response);
			},
			error: function(error){
				alert('error!');
				console.log(error);
			}
		});
	});

	var push_button = $('#push_button');
	push_button.on('click', function(e){
		e.preventDefault();
		var selected_user = $('#selected_user').val();
		var title = $('#title').val();
		var text = $('#text_content').val();
		var link = $('#link').val();
		$.ajax({
			url:ajaxurl,
			type:'POST',
			dataType:'json',
			data:{
				action: 'firebase_push_custom_notification',
				'selected_user':selected_user,
				'title':title,
				'text':text,
				'link':link
			},
			success:function(response){
				alert('success!');
				console.log(response);
			},
			error:function(error){
				alert('error!');
				console.log(error);
			}
		});
	})

	$("#selected_user").pqSelect({
		width: 300,
		multiplePlaceholder: 'Select user',
		checkbox: true,
		maxDisplay: 12
	}).on("change", function(e){
		var val=$(this).val();
	});

	$("#text_content").emojioneArea();

});