/**
 * sends ajax request to sync data from trends api
 */
 jQuery(function($){
    $(document).ready(function(){
        $('#sync_products_now').click(function(e){
            e.preventDefault();
            $.ajax({
                type:"POST",
                url :ajax_params.ajax_url,
				global: false,
                data :{
                    'action':'create_parent_cat'
                },
                beforeSend: function(){
                    $('.cog_trends_settings').css("opacity", '0.5');
                    $('.cog_trends_settings').css("pointer-events", 'none');
                    $('#loader_gif').show();
    
    
                },
                success: function(){
                    // console.log(createProducts());
                    createProducts();
				},
                error: function(){
                    alert('Products Sync failed');
                    $('.cog_trends_settings').css("opacity", 'unset');
                    $('.cog_trends_settings').css("pointer-events", 'unset');
                    $('#loader_gif').hide();
                },    
            })
    
        })
    });
    })
    
    function childCheckBox(parentID) {
        var checkboxes = document.getElementById(parentID);
        checkboxes.checked = true;
    }
    
    function createProducts() {
        var selected = []
        $('li input:checked.child_categories').each(function(){
            if(($('li input:checked'))) {
                selected.push($(this).val());
            }
        });
    
        $.each(selected, function(index){
            console.log(selected[index]);
            jQuery(function($){
                $.ajax({
                    type:"POST",
                    url :ajax_params.ajax_url,
// 					global: false,
                    data :{
                        'action':'create_products',
                        'number': selected[index],
                        'category': '',
                        'page' : ''
                    },
                    beforeSend: function(){
                        console.log('before_send');
                    },
                    success: function(result){
                        result = JSON.parse(result);
                        if(result.repeat === "true"){
                            loopCreate(result);
                            
                        }
                        console.log('Producted Sync Successful');
                    },
                    error: function(){
                        console.log('error');
                    },    
                })
            })
        })
		return true;
    }
    
    function loopCreate(result){
        jQuery(function($){
            $.ajax({
                type:"POST",
                url :ajax_params.ajax_url,
				global: false,
                data :{
                    'action':'create_products',
                    'number': result.cat_no,
                    'category': result.cat_id,
                    'page' : result.page_no
                },
                beforeSend: function(){
                    console.log('before_send');
                },
                success: function(result){
                    result = JSON.parse(result);
                    if(result.repeat === "true"){
                        loopCreate(result);
                    }
                },
                error: function(){
                    console.log('error');
                },    
            })
        })
		return "yes";
    }
    
    
    
    var selected = []
    $('li input:checked.child_categories').each(function(){
        if(($('li input:checked'))) {
            selected.push($(this).val());
        }
    });


 