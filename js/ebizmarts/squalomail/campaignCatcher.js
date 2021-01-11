function getCampaign() {
    let urlparams = null;
    let isGet = location.search.search('\\?');
    let sqm_cid = null;
    let isSqualomail = false;

    if (isGet !== -1) {
        urlparams = getUrlVars();
        urlparams.forEach(
            function (item) {
                if (item.key === 'utm_source') {
                    let reg = /^squalomail$/;

                    if (reg.exec(item.value)) {
                        isSqualomail = true;
                    }
                } else {
                    if (item.key === 'sqm_cid') {
                        sqm_cid = item.value;
                    }
                }
            }
        );
    } else {
        urlparams = location.href.split('/');
        let utmIndex = jQuery.inArray('utm_source', urlparams);
        let sqmcidIndex = jQuery.inArray('sqm_cid', urlparams);

        if (utmIndex !== -1) {
            let value = urlparams[utmIndex + 1];
            let reg = /^squalomail$/;

            if (reg.exec(value)) {
                isSqualomail = true;
            }
        } else {
            if (sqmcidIndex !== -1) {
                sqm_cid = urlparams[sqmcidIndex + 1];
            }
        }
    }

    if (sqm_cid && !isSqualomail) {
        Mage.Cookies.clear('squalomail_campaign_id');
        Mage.Cookies.set('squalomail_campaign_id', sqm_cid);
    }

    let landingPage = Mage.Cookies.get('squalomail_landing_page');

    if (!landingPage) {
        Mage.Cookies.set('squalomail_landing_page', location);
    }

    if (isSqualomail) {
        Mage.Cookies.clear('squalomail_campaign_id');
        Mage.Cookies.set('squalomail_landing_page', location);
    }
}

function getUrlVars() {
    let vars = [];
    let i = 0;
    window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,
        function (m, key, value) {
            vars[i] = {'value': value, 'key': key};
            i++;
        }
    );
    return vars;
}

if (document.loaded) {
    getCampaign();
} else {
    document.observe('dom:loaded', getCampaign);
}
