import {Page, Card, DataTable, Banner} from '@shopify/polaris';
import axios from 'axios';
import {useEffect, useState} from 'react';

function App() {
    const [recs, setRecs] = useState([]);
    const shop = new URLSearchParams(window.location.search).get('shop') || 'nitulabs.myshopify.com';
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(true);
    useEffect(() => {
        axios.get('https://shopifybackend.bcstdr.site/api/recommendations?shop=' + shop, {
            headers: {
                'ngrok-skip-browser-warning': 'true'
            }
        })
            .then((res) => {
                setRecs(res.data.recommendations || []);
                setError(null);
            })
            .catch((e) => {
                setError('Failed to load recommendations.');
                console.error(e);
            })
            .finally(() => setLoading(false));
    }, [shop]);
    return (
        <Page title="AI Smart Upsell Pro">
            <Banner status="info">All data for {shop}</Banner>
            <Card>
                <DataTable
                    columnContentTypes={['text','text','text']}
                    headings={['Product','Upsell','Algo']}
                    rows={recs.map(r => [r.product_title, r.upsell_title, r.algo])}
                />
            </Card>
        </Page>
    );
}
export default App;
