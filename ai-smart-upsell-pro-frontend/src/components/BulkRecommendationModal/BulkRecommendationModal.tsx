import React, {useCallback, useEffect, useState} from 'react';
import {Autocomplete, Banner, BlockStack, InlineStack, Modal, Text, Thumbnail,} from '@shopify/polaris';

interface ProductOption {
    label: string;
    value: string;
    imageSrc?: string;
}

interface Props {
    open: boolean;
    shop: string | null;
    existingPairs: { product_id: number; recommended_product_id: number }[];
    onClose: () => void;
    onSaved: () => void;
}

export function BulkRecommendationModal({
                                            open,
                                            shop,
                                            existingPairs,
                                            onClose,
                                            onSaved,
                                        }: Props) {
    const [sourceId, setSourceId] = useState('');
    const [recommendedIds, setRecommendedIds] = useState<string[]>([]);
    const [sourceSearch, setSourceSearch] = useState('');
    const [recommendedSearch, setRecommendedSearch] = useState('');
    const [sourceOptions, setSourceOptions] = useState<ProductOption[]>([]);
    const [recommendedOptions, setRecommendedOptions] = useState<ProductOption[]>([]);
    const [loadingSource, setLoadingSource] = useState(false);
    const [loadingRecommended, setLoadingRecommended] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const debounce = (func: Function, delay: number) => {
        let timeout: ReturnType<typeof setTimeout>;
        return (...args: any[]) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), delay);
        };
    };

    const fetchProductOptions = async (term: string): Promise<ProductOption[]> => {
        if (!shop) return [];
        const res = await fetch(
            `https://shopifybackend.bcstdr.site/api/products?shop=${shop}&search=${encodeURIComponent(term)}`
        );
        const data = await res.json();
        return data.map((p: any) => ({
            label: p.title,
            value: p.shopify_product_id.toString(),
            imageSrc: p.images?.[0]?.src || undefined,
        }));
    };

    const loadSources = useCallback(
        debounce(async (term: string) => {
            setLoadingSource(true);
            const options = await fetchProductOptions(term);
            setSourceOptions(options);
            setLoadingSource(false);
        }, 300),
        [shop]
    );

    const loadRecommendations = useCallback(
        debounce(async (term: string) => {
            setLoadingRecommended(true);
            const options = await fetchProductOptions(term);

            // Filter out existing pairs for this source
            const filtered = !sourceId
                ? options
                : options.filter(
                    (opt) =>
                        !existingPairs.some(
                            (pair) =>
                                pair.product_id.toString() === sourceId &&
                                pair.recommended_product_id.toString() === opt.value
                        )
                );

            setRecommendedOptions(filtered);
            setLoadingRecommended(false);
        }, 300),
        [shop, sourceId, existingPairs]
    );

    useEffect(() => {
        if (open) {
            setSourceId('');
            setRecommendedIds([]);
            setSourceSearch('');
            setRecommendedSearch('');
            setSourceOptions([]);
            setRecommendedOptions([]);
            setError(null);
            loadSources('');
            loadRecommendations('');
        }
    }, [open]);

    const getLabel = (id: string, options: ProductOption[]) =>
        options.find((opt) => opt.value === id)?.label || '';

    const getImage = (id: string, options: ProductOption[]) =>
        options.find((opt) => opt.value === id)?.imageSrc;

    const handleSave = async () => {
        setError(null);
        if (!sourceId || recommendedIds.length === 0 || !shop) {
            setError('Please select 1 source and at least 1 recommended product.');
            return;
        }

        setLoading(true);
        try {
            const res = await fetch('https://shopifybackend.bcstdr.site/api/recommendations/bulk-create', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    shop,
                    product_id: Number(sourceId),
                    recommended_product_ids: recommendedIds.map(Number),
                    algo: 'manual',
                }),
            });

            if (!res.ok) {
                const data = await res.json();
                throw new Error(data.message || 'Bulk create failed');
            }

            onSaved();
            onClose();
        } catch (err: any) {
            setError(err.message || 'Unexpected error during save.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Bulk Create Recommendations"
            primaryAction={{
                content: loading ? 'Saving...' : 'Create',
                onAction: handleSave,
                disabled: loading || !sourceId || recommendedIds.length === 0,
            }}
            secondaryActions={[{content: 'Cancel', onAction: onClose}]}
        >
            <Modal.Section>
                <BlockStack gap={"025"}>
                    {error && (
                        <Banner title="Error" tone="critical" onDismiss={() => setError(null)}>
                            {error}
                        </Banner>
                    )}

                    {/* Source */}
                    <Autocomplete
                        options={sourceOptions}
                        selected={sourceId ? [sourceId] : []}
                        onSelect={([val]) => setSourceId(val)}
                        textField={
                            <Autocomplete.TextField
                                label="Source Product"
                                onFocus={() => {
                                    if (sourceOptions.length <= 1) loadSources('');
                                }}
                                onChange={(val) => {
                                    setSourceSearch(val);
                                    loadSources(val);
                                }}
                                value={getLabel(sourceId, sourceOptions) || sourceSearch}
                                placeholder="Search products..."
                                autoComplete={"off"}/>
                        }
                        loading={loadingSource}
                    />

                    {/* Preview ⬇ */}
                    {sourceId && (
                        <div style={{display: 'flex', marginTop: 16, alignItems: 'center', gap: 8}}>
                            <Thumbnail source={getImage(sourceId, sourceOptions) || ''} size="small" alt="source"/>
                            <Text as={"p"} tone="subdued">{getLabel(sourceId, sourceOptions)}</Text>
                        </div>
                    )}

                    {/* Recommended */}
                    <Autocomplete
                        options={recommendedOptions}
                        selected={recommendedIds}
                        onSelect={(vals) => setRecommendedIds(vals)}
                        allowMultiple
                        textField={
                            <Autocomplete.TextField
                                label="Recommended Products"
                                onFocus={() => {
                                    if (recommendedOptions.length <= 1) loadRecommendations('');
                                }}
                                onChange={(val) => {
                                    setRecommendedSearch(val);
                                    loadRecommendations(val);
                                }}
                                value={recommendedSearch}
                                placeholder="Search and select multiple products..."
                                autoComplete={"off"}/>
                        }
                        loading={loadingRecommended}
                    />

                    {/* Preview selected */}
                    {recommendedIds.map((id) => (
                        <div style={{display: 'flex', marginTop: 16, alignItems: 'center', gap: 8}}>
                            <Thumbnail source={getImage(id, recommendedOptions) || ''} size="small" alt={"Recommended Image"}/>
                            <Text as={"p"} tone="subdued">{getLabel(id, recommendedOptions)}</Text>
                        </div>
                    ))}
                </BlockStack>
            </Modal.Section>
        </Modal>
    );
}
