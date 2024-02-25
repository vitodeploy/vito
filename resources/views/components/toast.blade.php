<div id="toast" hx-swap-oob="true">
    <script>
        window.addEventListener('toast', (e) => {
            window.toastr[e.detail.type](e.detail.message)
        });
    </script>
    @if(session()->has('toast.type') && session()->has('toast.message'))
        <script defer>
            window.toastr['{{ session()->get('toast.type') }}']('{{ session()->get('toast.message') }}');
        </script>
    @endif
</div>
