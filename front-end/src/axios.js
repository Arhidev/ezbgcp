import axios from "axios";
import {Keys} from '/src/config.js'

axios.defaults.baseURL = Keys.VUE_APP_API_URL + "/api"; // change this if you want to use a different url for APIs
//axios.defaults.withCredentials = true;
axios.defaults.headers.common['Authorization'] = 'Bearer ' + localStorage.getItem('freshToken');
