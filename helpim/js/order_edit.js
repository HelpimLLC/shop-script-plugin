$(function () {
    let $shippingTr = $('#shipping_methods').parent().parent().parent();
    let $helpimShipping = $('#helpim_shipping');

    $shippingTr.after($helpimShipping);
});
