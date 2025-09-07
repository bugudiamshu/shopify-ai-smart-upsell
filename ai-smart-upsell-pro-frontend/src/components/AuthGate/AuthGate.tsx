import React, { useEffect, useState } from 'react';

export default function AuthGate({ onAuthSuccess }: any) {
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const shop = params.get('shop');
        const host = params.get('host');

        if (!shop || !host) {
            console.error("Missing shop or host in query params");
            setLoading(false);
            return;
        }

        // Check if shop is already installed
        fetch(`https://shopifybackend.bcstdr.site/api/check-shop?shop=${shop}`, {
            credentials: 'include',
        })
            .then((res) => {
                if (res.ok) {
                    // ✅ Installed
                    onAuthSuccess();
                } else {
                    // ❌ Not installed → redirect to OAuth
                    window.location.href = `https://shopifybackend.bcstdr.site/shopify/install?shop=${shop}&host=${host}`;
                }
            })
            .catch((err) => {
                console.error("Auth check failed:", err);
                window.location.href = `https://shopifybackend.bcstdr.site/shopify/install?shop=${shop}&host=${host}`;
            })
            .finally(() => setLoading(false));
    }, [onAuthSuccess]);

    if (loading) {
        return (
            <div style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                height: '100vh',
                fontFamily: 'sans-serif'
            }}>
                <div style={{
                    width: 40,
                    height: 40,
                    border: '4px solid rgba(0,0,0,0.1)',
                    borderTop: '4px solid #000',
                    borderRadius: '50%',
                    animation: 'spin 1s linear infinite'
                }} />

                <style>{`
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `}</style>
            </div>
        );
    }

    return null;
}
