import React from 'react';
import {Box, Text, Tooltip} from '@shopify/polaris';
import {Icon} from '../RecommendationStats/RecommendationStats';

interface StatsProps {
    impressions: number;
    clicks: number;
    conversions: number;
    conversionRate: number;
}

export default function RecommendationStatsCell({impressions, clicks, conversions, conversionRate}: StatsProps) {
    const stats = [
        {
            // label: 'Impressions',
            value: impressions,
            icon: <Icon type={"eye"}/>,
            color: 'blue',
            tooltip: 'Number of times upsell product was shown',
        },
        {
            // label: 'Clicks',
            value: clicks,
            icon: <Icon type={"cursor"}/>,
            color: 'purple',
            tooltip: 'Number of times upsell product was clicked',
        },
        {
            // label: 'Conversions',
            value: conversions,
            icon: <Icon type={"check"}/>,
            color: 'green',
            tooltip: 'Number of times upsell product was purchased',
        },
        {
            // label: 'Conversion Rate',
            value: conversionRate ? `${conversionRate.toFixed(2)}%` : '0.00%',
            icon: <Icon type={"chart"}/>,
            color: 'red',
            tooltip: 'Clicks that resulted in purchases',
            isRate: true,
        },
    ];

    return (
        <div style={{display: "flex", gap: 4, flexWrap: "nowrap", justifyContent: "center"}}>
            {stats.map(({value, icon, color, tooltip, isRate}) => (
                <Tooltip key={value} content={tooltip}>
                    <div style={{textAlign: "center", padding: "2px", width: "65px"}}>
                        {icon}
                        {/*<Text variant="bodySm" as="p" fontWeight="medium" truncate>*/}
                        {/*    {label}*/}
                        {/*</Text>*/}
                        <Text variant={isRate ? 'headingSm' : 'headingMd'} as="p" fontWeight="bold" truncate>
                            {value.toLocaleString ? value.toLocaleString() : value}
                        </Text>
                    </div>
                </Tooltip>
            ))}
        </div>
    );
}
