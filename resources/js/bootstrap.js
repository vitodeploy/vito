import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import toastr from 'toastr';

window.toastr = toastr;

window.toastr.options = {
    "debug": false,
    "positionClass": "toast-bottom-right",
    "preventDuplicates": true,
}
