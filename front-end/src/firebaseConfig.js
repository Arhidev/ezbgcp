// Import the functions you need from the SDKs you need

import firebase from 'firebase/compat/app';

// Your web app's Firebase configuration

let apiKey = process.env.VUE_APP_API_KEY
let authDomain = process.env.VUE_APP_AUTH_DOMAIN
let projectId = process.env.VUE_APP_PROJECT_ID
let storageBucket = process.env.VUE_APP_STORAGE_BUCKET
let messagingSenderId = process.env.VUE_APP_MESSAGING_SENDER_ID
let appId = process.env.VUE_APP_APP_ID
const firebaseConfig = {
  apiKey: apiKey,
  authDomain: authDomain,
  projectId: projectId,
  storageBucket: storageBucket,
  messagingSenderId: messagingSenderId,
  appId: appId
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig)