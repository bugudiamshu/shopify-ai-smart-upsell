import React from 'react';
import ReactDOM from 'react-dom/client';
import {AppProvider} from '@shopify/polaris';
import en from '@shopify/polaris/locales/en.json';

import '@shopify/polaris/build/esm/styles.css';
import App from "./App";

const root = ReactDOM.createRoot(document.getElementById('root')!);
root.render(
    <AppProvider i18n={en}>
        <App/>
    </AppProvider>
);
