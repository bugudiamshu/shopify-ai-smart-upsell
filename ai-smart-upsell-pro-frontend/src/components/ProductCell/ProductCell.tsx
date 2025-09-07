import React from "react";
import {Thumbnail, Tooltip, Text, SkeletonThumbnail} from '@shopify/polaris';
import RecommendationStatsCell from "../RecommendationStatsCell";

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

interface Props {
    id: number;
    product?: Product;
    placeholder: string;
    shop: string | null;
    onImageClick: (src: string) => void;
    stats?: {
        impressions: number;
        clicks: number;
        conversions: number;
        conversionRate: number;
    };
}

export function ProductCell({id, product, placeholder, shop, onImageClick, stats}: Props) {
    const image = product?.images?.[0]?.src || placeholder;
    const title = product?.title || `#${id}`;
    const price = product?.price || '';
    const adminLink = shop ? `https://${shop}/admin/products/${id}` : undefined;

    return (
        <div style={{display: 'flex', alignItems: 'center', gap: 10, minWidth: 260}}>
            <div style={{cursor: 'zoom-in'}} onClick={() => onImageClick(image)}>
                <Thumbnail source={image} alt={title} size="small"/>
            </div>
            {product ? (
                <Tooltip content={title}>
                    <a
                        href={adminLink}
                        target="_blank"
                        rel="noopener noreferrer"
                        style={{
                            textDecoration: 'none',
                            color: 'inherit',
                            display: 'flex',
                            flexDirection: 'column',
                        }}
                    >
                        <Text as={"p"} variant="bodyMd" fontWeight="medium" truncate>
                            {title.length > 28 ? title.slice(0, 28) + '…' : title}
                        </Text>
                        {price && (
                            <Text as={"p"} variant="bodySm" tone="subdued">
                                ₹{price}
                            </Text>
                        )}

                        {stats &&
                            <RecommendationStatsCell
                                key={`stats-${product.id}`}
                                impressions={stats.impressions || 0}
                                clicks={stats.clicks || 0}
                                conversions={stats.conversions || 0}
                                conversionRate={stats.conversionRate || 0}
                            />
                        }
                    </a>
                </Tooltip>
            ) : (
                <SkeletonThumbnail size="small"/>
            )}
        </div>
    );
}
