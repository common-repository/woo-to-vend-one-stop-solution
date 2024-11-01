(function ($) {

    "use strict";
    $(document).on('ready',function () {

        $('.wv_sync').on('click',function () {

            var self = $(this);

            // self.parents('tr').children('td.post_id')

           var vend_id = self.data('vend_id')
            jQuery.ajax({
                type: 'POST',
                url: ajax_data.ajaxurl,
                data : {
                    action : 'sync_vend_data',
                    nonce : ajax_data.nonce,
                    id: vend_id
                },
                beforeSend:function () {
                  $('.loader--style3').css({'display':'block'});

                },
                success: function(response){
                    $('.loader--style3').css({'display':'none'});
                    alert("This Product is added to WooCommerce & the product id is "+response)
                    self.remove();

                }
            });

        })

        $('#set_webhook').on('click',function () {
            jQuery.ajax({
                type: 'POST',
                url: ajax_data.ajaxurl,
                data : {
                    action : 'set_vend_hook',
                    nonce : ajax_data.nonce,
                    'set_hook':true
                },
                beforeSend:function () {
                    $('.loader--style3').css({'display':'block'});

                },
                success: function(response){
                    $('.loader--style3').css({'display':'none'});
                    alert('Done, Please reload the page')
                    self.remove();
                    window.location.reload()

                }
            });
        })

        $('#vend_product_fetch').on('change',function () {
           var $select_val = $(".wv_selected_id:checked").map(function(){return $(this).val();}).get();

            var self = $(this);
            if(self.val() == 'selected'){
                var data =  $select_val.toString();
            }else if(self.val() == 'all'){
                data = 'all';
            }

            if(data == undefined)return;
            jQuery.ajax({
                type: 'POST',
                url: ajax_data.ajaxurl,
                data : {
                    action : 'sync_vend_data',
                    nonce : ajax_data.nonce,
                    data: data
                },
                beforeSend:function () {
                    $('.loader--style3').css({'display':'block'});

                },
                success: function(response){
                    $('.loader--style3').css({'display':'none'});
                    alert("Inserted Products are "+response)

                }
            });
            
        })

    })

})(jQuery)