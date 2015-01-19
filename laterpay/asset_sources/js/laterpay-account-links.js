// render LaterPay purchase dialogs using the LaterPay YUI dialog manager library
YUI().use('node', 'laterpay-dialog', 'laterpay-iframe', 'laterpay-easyxdm', function(Y) {

    // render account links iframe
    if (!lpAccountLinksUrl) {
        return;
    }

    new Y.LaterPay.IFrame(
        Y.one('.lp_account-links'),
        lpAccountLinksUrl,
        {
            width       : '140',
            height      : '28',
            scrolling   : 'no',
            frameborder : '0',
        }
    );

});