<div>
    <style>
        #htmx-error-modal-backdrop {
            display: none; /* Hide by default */
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
        }
        #htmx-error-modal-content {
            background-color: #fefefe;
            margin: 50px auto; /* 200px from the top and centered */
            padding: 0;
            width: calc(100% - 100px); /* Full width minus the margin */
            height: calc(100% - 100px); /* Full height minus the margin */
            position: relative;
        }
        #htmx-error-modal-content iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
    <div id="htmx-error-modal-backdrop" onclick="closeHtmxErrorModal()">
        <div id="htmx-error-modal-content" onclick="event.stopPropagation()"></div>
    </div>
    <script>
        function closeHtmxErrorModal() {
            document.getElementById('htmx-error-modal-backdrop').style.display = 'none';
            document.getElementById('htmx-error-modal-content').innerHTML = '';
        }
        document.body.addEventListener('htmx:beforeOnLoad', function (evt) {
            if (evt.detail.xhr.status >= 400) {
                let iframe = document.createElement('iframe');
                document.getElementById('htmx-error-modal-content').appendChild(iframe);
                iframe.src = 'about:blank';
                iframe.contentWindow.document.open();
                iframe.contentWindow.document.write(evt.detail.xhr.responseText);
                iframe.contentWindow.document.close();
                document.getElementById('htmx-error-modal-backdrop').style.display = 'block';
            }
        });
    </script>
</div>
