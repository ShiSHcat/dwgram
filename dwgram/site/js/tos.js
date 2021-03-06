"use strict";
(function() {
        // show TOS and privacy policy as an alert.
        var container = document.createElement('div');
        container.className = 'mdc-dialog mdc-dialog--scrollable';
        let dialogContainer = document.createElement('div');
        dialogContainer.className = 'mdc-dialog__container';
        container.appendChild(dialogContainer);
        let dialogScrim = document.createElement('div');
        dialogScrim.className = 'mdc-dialog__scrim';
        container.appendChild(dialogScrim);
        let dialogSurface = document.createElement('div');
        dialogSurface.className = 'mdc-dialog__surface';
        dialogContainer.appendChild(dialogSurface);
        let dialogTitle = document.createElement('h2');
        dialogTitle.className = 'mdc-dialog__title';
        dialogTitle.innerHTML = 'Before you proceeed...';
        dialogSurface.appendChild(dialogTitle);
        let dialogText = document.createElement('div');
        dialogText.className = 'mdc-dialog__content';
        dialogSurface.appendChild(dialogText);
        let dialogTextTitle1 = document.createElement('h3');
        dialogTextTitle1.className = 'mdc-typography';
        dialogTextTitle1.innerHTML = 'Terms of service:';
        dialogText.appendChild(dialogTextTitle1);
        let dialogText1 = document.createElement('div');
        dialogText1.className = 'mdc-typography';
        dialogText1.innerHTML = 'By using DWgram you accept our Privacy Policy and agree not to use our service in violation of Telegram\'s terms of service.<br>\
                                 You also agree that you won\'t use the service to share other illegal content, included but not limited to malware and terrorism.\
                                 We take the right to block access to media that don\'t respect these terms.\
                                 We reserve the right to send your media data and IP to authorities on their request.';
        dialogText.appendChild(dialogText1);
        let dialogTextTitle2 = document.createElement('h3');
        dialogTextTitle2.className = 'mdc-typography';
        dialogTextTitle2.innerHTML = 'Privacy Policy:';
        dialogText.appendChild(dialogTextTitle2);
        let dialogText2 = document.createElement('div');
        dialogText2.className = 'mdc-typography';
        dialogText2.innerHTML = 'We store data needed by our system to fetch the files uploaded on Telegram\'s cloud, such as:\
        <ol><li>The message ID</li><li>Where is the message going (a supergroup, a channel etc.) ("peer_id") (includes the joinchat fragment/username)</li></ol>\
        <br><br>\
        <b>By using our service with joinhash you agree that you are allowed to see the chat contents and you aren\'t banned from the channel/supergroup.</b><br>\
        We will keep your IP in our system for ratelimitation and to fight abuse.<br>Your IP will be stored in an Amsterdam, Netherlands (EU) datacenter.<br>Abuse: abuse@dwgram.xyz\
        ';
        dialogText.appendChild(dialogText2);
        document.body.appendChild(container);
        let dialogFooter = document.createElement('footer');
        dialogFooter.className = 'mdc-dialog__actions';
        dialogSurface.appendChild(dialogFooter);
        let no = document.createElement('button');
        no.className = 'mdc-button mdc-dialog__button';
        no.setAttribute('data-mdc-dialog-action', 'decline');
        let noRipple = document.createElement('div');
        noRipple.className = 'mdc-button__ripple';
        no.appendChild(noRipple);
        let noLabel = document.createElement('span');
        noLabel.className = 'mdc-button__label';
        noLabel.innerHTML = 'Decline';
        no.appendChild(noLabel);
        dialogFooter.appendChild(no);
        let ok = document.createElement('button');
        ok.className = 'mdc-button mdc-dialog__button';
        ok.setAttribute('data-mdc-dialog-action', 'accept');
        let okRipple = document.createElement('div');
        okRipple.className = 'mdc-button__ripple';
        ok.appendChild(okRipple);
        let okLabel = document.createElement('span');
        okLabel.className = 'mdc-button__label';
        okLabel.innerHTML = 'Accept';
        ok.appendChild(okLabel);
        dialogFooter.appendChild(ok);
        dialogContainer = dialogText = dialogTitle = dialogText1 = dialogText2 = dialogTextTitle1 = dialogTextTitle2 = no = ok = undefined;
        let dialog = new mdc.dialog.MDCDialog(container);
        dialog.listen('MDCDialog:closing', e => {
            document.body.removeChild(container);
            if (e.detail.action == 'accept')
                localStorage.setItem('acc', true);
            else
                document.location.assign('..');
        });
        dialog.open();
})();
