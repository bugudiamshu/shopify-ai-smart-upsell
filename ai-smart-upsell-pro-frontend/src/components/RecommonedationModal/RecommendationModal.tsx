import React, {useCallback, useEffect, useState} from 'react';
import {Autocomplete, Banner, BlockStack, Modal, Text, Thumbnail,} from '@shopify/polaris';

interface ProductOption {
    label: string;
    value: string;
    imageSrc?: string;
}

interface EditData {
    id: number;
    product_id: number;
    recommended_product_id: number;
}

interface Props {
    open: boolean;
    mode: 'create' | 'edit';
    editData?: EditData;
    existingPairs: { product_id: number; recommended_product_id: number }[];
    productMap: any;
    shop: string | null;
    onClose: () => void;
    onSaved: () => void;
}

export function RecommendationModal({
                                        open,
                                        mode,
                                        editData,
                                        existingPairs,
                                        productMap,
                                        shop,
                                        onClose,
                                        onSaved,
                                    }: Props) {
    const [sourceId, setSourceId] = useState('');
    const [recommendedId, setRecommendedId] = useState('');
    const [sourceSearch, setSourceSearch] = useState('');
    const [recommendedSearch, setRecommendedSearch] = useState('');
    const [sourceOptions, setSourceOptions] = useState<ProductOption[]>([]);
    const [recommendedOptions, setRecommendedOptions] = useState<ProductOption[]>([]);
    const [loadingSourceOptions, setLoadingSourceOptions] = useState(false);
    const [loadingRecommendedOptions, setLoadingRecommendedOptions] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [isDuplicate, setIsDuplicate] = useState(false);

    const debounce = (func: Function, delay: number) => {
        let timeout: ReturnType<typeof setTimeout>;
        return (...args: any[]) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), delay);
        };
    };

    const fetchProductOptions = async (search: string) => {
        if (!shop) return [];
        const res = await fetch(
            `https://shopifybackend.bcstdr.site/api/products?shop=${shop}&search=${encodeURIComponent(search)}`
        );
        const data = await res.json();
        if (!Array.isArray(data)) return [];
        return data.map((p: any) => ({
            label: p.title,
            value: p.shopify_product_id.toString(),
            imageSrc: p.images?.[0]?.src || undefined,
        }));
    };

    const fetchSourceOptions = useCallback(
        debounce(async (term: string) => {
            setLoadingSourceOptions(true);
            const options = await fetchProductOptions(term);
            setSourceOptions(options);
            setLoadingSourceOptions(false);
        }, 250),
        [shop]
    );

    const fetchRecommendedOptions = useCallback(
        debounce(async (term: string) => {
            setLoadingRecommendedOptions(true);
            const options = await fetchProductOptions(term);
            setRecommendedOptions(options);
            setLoadingRecommendedOptions(false);
        }, 250),
        [shop]
    );

    useEffect(() => {
        if (open) {
            if (mode === 'edit' && editData) {
                const srcProduct = productMap[editData.product_id];
                const recProduct = productMap[editData.recommended_product_id];

                const srcOption: ProductOption | null = srcProduct
                    ? {
                        label: srcProduct.title,
                        value: srcProduct.shopify_product_id.toString(),
                        imageSrc: srcProduct.images?.[0]?.src,
                    }
                    : null;

                const recOption: ProductOption | null = recProduct
                    ? {
                        label: recProduct.title,
                        value: recProduct.shopify_product_id.toString(),
                        imageSrc: recProduct.images?.[0]?.src,
                    }
                    : null;

                if (srcOption) {
                    setSourceId(srcOption.value);
                    setSourceOptions([srcOption]);
                }

                if (recOption) {
                    setRecommendedId(recOption.value);
                    setRecommendedOptions([recOption]);
                }

                setSourceSearch('');
                setRecommendedSearch('');
            } else {
                // default 'create' flow
                setSourceId('');
                setRecommendedId('');
                setSourceOptions([]);
                setRecommendedOptions([]);
                setSourceSearch('');
                setRecommendedSearch('');
                fetchSourceOptions('');
                fetchRecommendedOptions('');
            }

            setError(null);
            setIsDuplicate(false);
        }
    }, [open, mode, editData, productMap]);

    useEffect(() => {
        const sameIds = sourceId === recommendedId;
        const duplicate = existingPairs.some(
            (p) =>
                p.product_id.toString() === sourceId &&
                p.recommended_product_id.toString() === recommendedId &&
                !(mode === 'edit' && editData &&
                    editData.product_id.toString() === sourceId &&
                    editData.recommended_product_id.toString() === recommendedId)
        );
        setIsDuplicate(sameIds || duplicate);
    }, [sourceId, recommendedId, existingPairs, mode, editData]);

    const getLabel = (id: string, list: ProductOption[]) =>
        list.find((o) => o.value === id)?.label || '';
    const getImage = (id: string, list: ProductOption[]) =>
        list.find((o) => o.value === id)?.imageSrc || undefined;

    const handleSave = async () => {
        setError(null);
        if (!sourceId || !recommendedId || isDuplicate || !shop) {
            setError('Invalid or duplicate recommendation.');
            return;
        }

        setLoading(true);
        try {
            const url =
                mode === 'edit'
                    ? `https://shopifybackend.bcstdr.site/api/recommendations/${editData!.id}`
                    : `https://shopifybackend.bcstdr.site/api/recommendations/create`;

            const method = mode === 'edit' ? 'PATCH' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    shop,
                    product_id: Number(sourceId),
                    recommended_product_id: Number(recommendedId),
                    algo: 'manual',
                    enabled: true,
                }),
            });

            if (!response.ok) {
                const res = await response.json();
                throw new Error(res.message || 'Failed to save recommendation');
            }

            onSaved();
            onClose();
        } catch (err: any) {
            setError(err.message || 'Unexpected error.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title={mode === 'edit' ? 'Edit Recommendation' : 'Add Recommendation'}
            primaryAction={{
                content: loading ? (mode === 'edit' ? 'Saving…' : 'Creating…') : (mode === 'edit' ? 'Save' : 'Create'),
                onAction: handleSave,
                disabled:
                    !sourceId ||
                    !recommendedId ||
                    loading ||
                    loadingSourceOptions ||
                    loadingRecommendedOptions ||
                    isDuplicate,
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

                    {/* Source Autocomplete */}
                    <Autocomplete
                        options={sourceOptions}
                        selected={sourceId ? [sourceId] : []}
                        onSelect={([val]) => setSourceId(val)}
                        textField={
                            <Autocomplete.TextField
                                label="Source Product"
                                onFocus={() => {
                                    if (sourceOptions.length <= 1) fetchSourceOptions('');
                                }}
                                onChange={(val) => {
                                    setSourceSearch(val);
                                    fetchSourceOptions(val);
                                }}
                                value={getLabel(sourceId, sourceOptions) || sourceSearch}
                                placeholder="Search products..."
                                autoComplete={"off"}/>
                        }
                        loading={loadingSourceOptions}
                    />
                    {sourceId && (
                        <div style={{display: 'flex', marginTop: 16, alignItems: 'center', gap: 8}}>
                            <Thumbnail source={getImage(sourceId, sourceOptions) || ''} size="small" alt="source"/>
                            <Text as={"p"} tone="subdued">{getLabel(sourceId, sourceOptions)}</Text>
                        </div>
                    )}

                    {/* Recommended Autocomplete */}
                    <Autocomplete
                        options={recommendedOptions}
                        selected={recommendedId ? [recommendedId] : []}
                        onSelect={([val]) => setRecommendedId(val)}
                        textField={
                            <Autocomplete.TextField
                                label="Recommended Product"
                                onFocus={() => {
                                    if (recommendedOptions.length <= 1) fetchRecommendedOptions('');
                                }}
                                onChange={(val) => {
                                    setRecommendedSearch(val);
                                    fetchRecommendedOptions(val);
                                }}
                                value={
                                    getLabel(recommendedId, recommendedOptions) || recommendedSearch
                                }
                                placeholder="Search products..."
                                autoComplete={"off"}/>
                        }
                        loading={loadingRecommendedOptions}
                    />
                    {recommendedId && (
                        <div style={{display: 'flex', marginTop: 16, alignItems: 'center', gap: 8}}>
                            <Thumbnail source={getImage(recommendedId, recommendedOptions) || ''} size="small" alt="recommended"/>
                            <Text as={"p"} tone="subdued">{getLabel(recommendedId, recommendedOptions)}</Text>
                        </div>
                    )}
                </BlockStack>
            </Modal.Section>
        </Modal>
    );
}
