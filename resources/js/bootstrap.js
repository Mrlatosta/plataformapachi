import axios from 'axios';

axios.defaults.baseURL = window.location.origin; // Usará https:// con la URL pública actual
axios.defaults.withCredentials = true; // Si usas sesión/cookies

export default axios;

// import axios from 'axios';

// if (window.location.hostname === "127.0.0.1" || window.location.hostname === "localhost") {
//     axios.defaults.baseURL = "http://127.0.0.1:8000";
// } else {
//     axios.defaults.baseURL = window.location.origin; // producción usa https
// }

// axios.defaults.withCredentials = true;

// export default axios;
