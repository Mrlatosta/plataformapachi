import axios from 'axios';

axios.defaults.baseURL = window.location.origin; // Usará https:// con la URL pública actual
axios.defaults.withCredentials = true; // Si usas sesión/cookies

export default axios;
