import axios from 'axios';

axios.defaults.baseURL = import.meta.env.VITE_APP_URL;
axios.defaults.withCredentials = false;

export default axios;

// import axios from 'axios';

// if (window.location.hostname === "127.0.0.1" || window.location.hostname === "localhost") {
//     axios.defaults.baseURL = "http://127.0.0.1:8000";
// } else {
//     axios.defaults.baseURL = window.location.origin; // producción usa https
// }

// axios.defaults.withCredentials = true;

// export default axios;
