!function(t){t(function(){function i(){var i={postStatisticsPane:t("#lp_js_post-statistics"),postPreviewModeForm:t("#lp_plugin-preview-mode-form"),postPreviewModeToggle:t("#lp_js_toggle-post-preview-mode"),postPreviewModeInput:t("#lp_js_preview-post-input"),postStatisticsVisibilityForm:t("#lp_js_post-statistics-visibility-form"),postStatisticsVisibilityToggle:t("#lp_js_toggle-post-statistics-visibility"),postStatisticsVisibilityInput:t("#lp_js_hide-statistics-pane-input"),postContentPlaceholder:t("#lp_js_post-content-placeholder"),postStatisticsPlaceholder:t("#lp_js_post-statistics-placeholder"),purchaseLink:".lp_js_do-purchase",hidden:"lp_is_hidden"},e=function(){i.postStatisticsPane=t("#lp_js_post-statistics"),i.postPreviewModeForm=t("#lp_plugin-preview-mode-form"),i.postPreviewModeToggle=t("#lp_js_toggle-post-preview-mode"),i.postPreviewModeInput=t("#lp_js_preview-post-input"),i.postStatisticsVisibilityForm=t("#lp_js_post-statistics-visibility-form"),i.postStatisticsVisibilityToggle=t("#lp_js_toggle-post-statistics-visibility"),i.postStatisticsVisibilityInput=t("#lp_js_hide-statistics-pane-input")},s=function(){t("body").on("mousedown",i.purchaseLink,function(){d(this)}).on("click",i.purchaseLink,function(t){t.preventDefault()})},o=function(){i.postStatisticsVisibilityToggle.on("mousedown",function(){p()}).on("click",function(t){t.preventDefault()}),i.postPreviewModeToggle.on("change",function(){l()})},a=function(){t.get(lpVars.ajaxUrl,{action:"laterpay_post_statistic_render",post_id:lpVars.post_id,nonce:lpVars.nonces.statistic},function(t){t&&(i.postStatisticsPlaceholder.before(t).remove(),n())})},n=function(){e(),o(),t(".lp_sparkline-bar",i.postStatisticsPane).peity("bar",{delimiter:";",width:182,height:42,gap:1,fill:function(t,i,e){var s=new Date,o=e.length,a="#999";return s.setDate(s.getDate()-(o-i)),i===o-1&&(a="#555"),(0===s.getDay()||6===s.getDay())&&(a="#c1c1c1"),a}}),t(".lp_sparkline-background-bar",i.postStatisticsPane).peity("bar",{delimiter:";",width:182,height:42,gap:1,fill:function(){return"#ddd"}})},p=function(){var e=i.postStatisticsPane.hasClass(i.hidden)?"0":"1";i.postStatisticsVisibilityInput.val(e),i.postStatisticsPane.toggleClass(i.hidden),t.post(lpVars.ajaxUrl,i.postStatisticsVisibilityForm.serializeArray())},l=function(){i.postPreviewModeInput.val(i.postPreviewModeToggle.prop("checked")?1:0),t.post(lpVars.ajaxUrl,i.postPreviewModeForm.serializeArray(),function(){window.location.reload()})},r=function(){t.get(lpVars.ajaxUrl,{action:"laterpay_post_load_purchased_content",post_id:lpVars.post_id,nonce:lpVars.nonces.content},function(t){t&&i.postContentPlaceholder.html(t)})},c=function(){t.post(lpVars.ajaxUrl,{action:"laterpay_post_track_views",post_id:lpVars.post_id,nonce:lpVars.nonces.tracking})},d=function(i){t(i).data("preview-as-visitor")&&alert(lpVars.i18nAlert)},u=function(){1===t("#lp_js_post-content-placeholder").length&&(r(),c()),1===t("#lp_js_post-statistics-placeholder").length&&a(),s()};u()}i()})}(jQuery),YUI().use("node","laterpay-dialog","laterpay-iframe","laterpay-easyxdm",function(t){var i=(t.one(".lp_js_do-purchase"),{showCloseBtn:!0,canSkipAddToInvoice:!1}),e=new t.LaterPay.DialogManager;t.one(t.config.doc).delegate("click",function(t){if(t.preventDefault(),t.currentTarget.getData("preview-as-visitor"))alert(lpVars.i18nAlert);else{var s=t.currentTarget.getAttribute("href");t.currentTarget.hasAttribute("data-laterpay")&&(s=t.currentTarget.getAttribute("data-laterpay")),e.openDialog(s,i.showCloseBtn)}},".lp_js_do-purchase")});