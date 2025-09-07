import React, { createContext, useMemo, useState } from 'react';
import { AppProvider as PolarisAppProvider } from '@shopify/polaris';
import {AppBridgeState, ClientApplication, createApp} from '@shopify/app-bridge';
import en from '@shopify/polaris/locales/en.json';
import RecommendationsPage from './pages/RecommendationsPage/RecommendationsPage';
import AuthGate from './components/AuthGate/AuthGate';

export const AppBridgeContext = createContext<ClientApplication<AppBridgeState> | null>(null);

export default function App() {
    const [isAuthed, setIsAuthed] = useState(false);

    const searchParams = new URLSearchParams(window.location.search);
    const host = searchParams.get('host');

    const appBridge = useMemo(() => {
        if (!host) return null;

        return createApp({
            apiKey: '862a81c13ddf8d91720c091d700de88d', // TODO: move to env
            host,
            forceRedirect: true,
        });
    }, [host]);

    if (!appBridge) {
        return (
            <div style={{ padding: 32 }}>
                <p>Missing <code>host</code> parameter. Please launch the app from the Shopify Admin interface.</p>
            </div>
        );
    }

    return (
        <PolarisAppProvider i18n={en}>
            <AppBridgeContext.Provider value={appBridge}>
                {!isAuthed ? (
                    <AuthGate onAuthSuccess={() => setIsAuthed(true)} />
                ) : (
                    <RecommendationsPage />
                )}
            </AppBridgeContext.Provider>
        </PolarisAppProvider>
    );
}
