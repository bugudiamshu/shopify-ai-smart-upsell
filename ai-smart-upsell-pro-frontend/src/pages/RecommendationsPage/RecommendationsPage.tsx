import React, {useContext, useEffect, useState} from 'react';
import {Box, Button, Card, DataTable, Icon, Image, InlineStack, Modal, Page, Spinner, Text, TextField,} from '@shopify/polaris';
import {ChevronLeftIcon, ChevronRightIcon} from '@shopify/polaris-icons';
import {AppBridgeContext} from '../../App';
import axios from 'axios';
import {ProductCell} from "../../components/ProductCell/ProductCell";
import {RecommendationStatus} from "../../components/RecommendationStatus/RecommendationStatus";
import {RecommendationActions} from "../../components/RecommendationActions/RecommendationActions";
import {RecommendationModal} from "../../components/RecommonedationModal/RecommendationModal";
import {Toast} from '@shopify/app-bridge/actions';
import {BulkRecommendationModal} from "../../components/BulkRecommendationModal/BulkRecommendationModal";
import RecommendationStats from "../../components/RecommendationStats";
import {Icon as StatsIcon} from '../../components/RecommendationStats/RecommendationStats';

interface ProductImage {
    src: string;
    alt?: string;
}

interface Product {
    id: number;
    shopify_product_id: number;
    title: string;
    price?: string;
    images?: ProductImage[];
}

interface Recommendation {
    id: number;
    product_id: number;
    recommended_product_id: number;
    algo: string;
    enabled: boolean | number;
    impressions: number;
    clicks: number;
    conversions: number;
    conversion_rate: number;
}

const PLACEHOLDER = 'https://cdn.shopify.com/shopifycloud/web/assets/v1/bd3234c6090b17dd7c6751aa595817c8.svg';

export default function RecommendationsPage() {
    const app = useContext(AppBridgeContext);
    const shop = new URLSearchParams(window.location.search).get('shop');

    const [recommendations, setRecommendations] = useState<Recommendation[]>([]);
    const [productMap, setProductMap] = useState<Record<number, Product>>({});
    const [searchValue, setSearchValue] = useState('');
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [activeImage, setActiveImage] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [bulkModalOpen, setBulkModalOpen] = useState(false);
    const [syncing, setSyncing] = useState(false);

    // Modal
    const [modalOpen, setModalOpen] = useState(false);
    const [modalMode, setModalMode] = useState<'create' | 'edit'>('create');
    const [editingRec, setEditingRec] = useState<Recommendation | undefined>(undefined);

    const showToast = (message: string) => {
        if (!app) return;
        const toast = Toast.create(app, {message, duration: 3000});
        toast.dispatch(Toast.Action.SHOW);
    };

    useEffect(() => {
        const timeout = setTimeout(() => {
            setDebouncedSearch(searchValue);
            setPage(1);
        }, 300);
        return () => clearTimeout(timeout);
    }, [searchValue]);

    useEffect(() => {
        fetchRecs();
    }, [page, debouncedSearch]);

    const fetchRecs = async () => {
        setLoading(true);
        try {
            const res = await axios.get('https://shopifybackend.bcstdr.site/api/recommendations', {
                params: {shop, page, search: debouncedSearch || undefined},
            });

            setRecommendations(res.data.recommendations.data);
            setPage(res.data.recommendations.current_page);
            setTotalPages(res.data.recommendations.last_page);

            const map: Record<number, Product> = {};
            res.data.products.forEach((p: Product) => {
                map[p.shopify_product_id] = p;
            });
            setProductMap(map);
        } catch {
            showToast('Failed to load recommendations');
        } finally {
            setLoading(false);
        }
    };

    const deleteRec = async (recId: number) => {
        if (!window.confirm('Delete this recommendation?')) return;
        try {
            await axios.delete(`https://shopifybackend.bcstdr.site/api/recommendations/${recId}`);
            showToast('Deleted');
            fetchRecs();
        } catch {
            showToast('Failed to delete');
        }
    };

    const toggleEnabled = async (recId: number, enabled: boolean) => {
        try {
            await axios.patch(`https://shopifybackend.bcstdr.site/api/recommendations/${recId}/toggle`, {enabled});
            showToast('Updated');
            fetchRecs();
        } catch {
            showToast('Toggle failed');
        }
    };

    const openCreate = () => {
        setModalMode('create');
        setEditingRec(undefined);
        setModalOpen(true);
    };

    const openEdit = (rec: Recommendation) => {
        setModalMode('edit');
        setEditingRec(rec);
        setModalOpen(true);
    };

    const rows = recommendations.map((rec) => [
        <ProductCell
            id={rec.product_id}
            product={productMap[rec.product_id]}
            placeholder={PLACEHOLDER}
            shop={shop}
            onImageClick={setActiveImage}
        />,
        <ProductCell
            id={rec.recommended_product_id}
            product={productMap[rec.recommended_product_id]}
            placeholder={PLACEHOLDER}
            shop={shop}
            onImageClick={setActiveImage}
        />,
        rec.algo.charAt(0).toUpperCase() + rec.algo.slice(1).toLowerCase(),
        <RecommendationStatus enabled={rec.enabled}/>,
        <div style={{display: "flex", gap: 4, justifyContent: "center"}}>
            <StatsIcon type={"eye"}/>
            <Text as={"h4"}>{rec.impressions?.toLocaleString() || '0'}</Text>
        </div>,
        <div style={{display: "flex", gap: 4, justifyContent: "center"}}>
            <StatsIcon type={"cursor"}/>
            <Text as={"h4"}>{rec.clicks?.toLocaleString() || '0'}</Text>
        </div>,
        <div style={{display: "flex", gap: 4, justifyContent: "center"}}>
            <StatsIcon type={"check"}/>
            <Text as={"h4"}>{rec.conversions?.toLocaleString() || '0'}</Text>
        </div>,
        <div style={{display: "flex", gap: 4, justifyContent: "center"}}>
            <StatsIcon type={"chart"}/>
            <Text as={"h4"}>{rec.conversion_rate ? rec.conversion_rate + '%' : '0.00%'}</Text>
        </div>,
        <RecommendationActions
            enabled={rec.enabled}
            onToggle={() => toggleEnabled(rec.id, !rec.enabled)}
            onDelete={() => deleteRec(rec.id)}
            onEdit={() => openEdit(rec)}
        />,
    ]);

    const handleSyncProducts = async () => {
        if (!window.confirm('Sync products from Shopify now? This may take a while.')) return;

        setSyncing(true);
        try {
            await axios.post('https://shopifybackend.bcstdr.site/api/products/sync', {shop});
            showToast('Product sync complete');
            await fetchRecs();
        } catch {
            showToast('Product sync failed');
        } finally {
            setSyncing(false);
        }
    };

    return (
        <>
            <Page fullWidth title={"Upsell Recommendations"}
                  primaryAction={{
                      content: '+ Add Recommendation',
                      onAction: () => openCreate(), // opens the modal in 'create' mode
                  }}
                  secondaryActions={[
                      {
                          content: '++ Bulk Add Recommendations',
                          onAction: () => setBulkModalOpen(true), // Replace with your actual function
                      },
                      {
                          content: syncing ? 'Syncing Products...' : 'Sync Products',
                          onAction: handleSyncProducts,
                          disabled: syncing,
                      },
                  ]}
            >
                <div style={{marginBottom: 16}}>
                    <RecommendationStats shop={shop} showToast={showToast}/>
                </div>

                <div style={{display: 'flex', justifyContent: 'space-between', marginBottom: 16}}>
                    <div style={{width: 400}}>
                        <TextField
                            label="Search"
                            labelHidden
                            placeholder="Search product"
                            value={searchValue}
                            onChange={setSearchValue}
                            clearButton
                            onClearButtonClick={() => setSearchValue('')}
                            autoComplete={"off"}/>
                    </div>
                    {totalPages > 1 && (
                        <div style={{display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8}}>
                            <Button
                                icon={<Icon source={ChevronLeftIcon}/>}
                                disabled={page <= 1}
                                onClick={() => setPage(p => Math.max(p - 1, 1))}
                            />
                            <Text as={"p"}>Page</Text>
                            <select
                                value={page}
                                onChange={e => setPage(Number(e.target.value))}
                                style={{padding: '4px 8px', borderRadius: 4}}
                            >
                                {Array.from({length: totalPages}, (_, i) => (
                                    <option key={i + 1} value={i + 1}>{i + 1}</option>
                                ))}
                            </select>
                            <Text as={"p"}>of {totalPages}</Text>
                            <Button
                                icon={<Icon source={ChevronRightIcon}/>}
                                disabled={page >= totalPages}
                                onClick={() => setPage(p => p + 1)}
                            />
                        </div>
                    )}
                </div>

                <div style={{marginBottom: 24}}>
                    <Card>
                        {loading ? (
                            <InlineStack align={"center"}>
                                <Spinner size="large"/>
                            </InlineStack>
                        ) : rows.length > 0 ?
                            (
                                <DataTable
                                    columnContentTypes={['text', 'text', 'text', 'text', 'numeric', 'numeric', 'numeric', 'text', 'text']}
                                    headings={['Product', 'Recommended', 'Algorithm', 'Status', 'Impressions', 'Clicks', 'Conversions', 'Conv. Rate', 'Actions']}
                                    rows={rows}
                                    verticalAlign="middle"
                                />
                            ) :
                            <Box>No Recommendations</Box>
                        }
                    </Card>
                </div>

                {activeImage && (
                    <Modal open onClose={() => setActiveImage(null)} title="Product Image" size="large">
                        <Modal.Section>
                            <Image alt="Product" source={activeImage} style={{maxWidth: '100%', display: 'block', margin: 'auto'}}/>
                        </Modal.Section>
                    </Modal>
                )}

                <RecommendationModal
                    open={modalOpen}
                    mode={modalMode}
                    editData={editingRec && {
                        id: editingRec.id,
                        product_id: editingRec.product_id,
                        recommended_product_id: editingRec.recommended_product_id,
                    }}
                    shop={shop}
                    productMap={productMap}
                    existingPairs={recommendations.map(r => ({
                        product_id: r.product_id,
                        recommended_product_id: r.recommended_product_id,
                    }))}
                    onClose={() => setModalOpen(false)}
                    onSaved={() => {
                        showToast('Saved');
                        (async () => fetchRecs())();
                    }}
                />

                <BulkRecommendationModal
                    open={bulkModalOpen}
                    shop={shop}
                    existingPairs={recommendations.map((r) => ({
                        product_id: r.product_id,
                        recommended_product_id: r.recommended_product_id,
                    }))}
                    onClose={() => setBulkModalOpen(false)}
                    onSaved={() => {
                        showToast('Recommendations saved');
                        (async () => fetchRecs())();
                    }}
                />
            </Page>
        </>
    );
}
