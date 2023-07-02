<div>
    <script>
        window.addEventListener('toast', (e) => {
            window.toastr[e.detail.type](e.detail.message)
        });
    </script>
    @if(session()->has('toast.type') && session()->has('toast.message'))
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                window.toastr['{{ session()->get('toast.type') }}']('{{ session()->get('toast.message') }}');
            });
        </script>
    @endif
</div>
