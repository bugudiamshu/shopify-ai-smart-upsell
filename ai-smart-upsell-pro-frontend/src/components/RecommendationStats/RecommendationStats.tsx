import React, { useEffect, useState } from 'react';
import axios from 'axios';

interface RecommendationStatsProps {
    shop: string | null;
    showToast: (msg: string) => void;
}

interface StatsData {
    total_impressions: number;
    total_clicks: number;
    total_conversions: number;
}

export const Icon = ({ type }: { type: 'eye' | 'cursor' | 'check' | 'chart' }) => {
    const style = { width: 20, height: 20, strokeWidth: 2, stroke: 'currentColor', fill: 'none' };
    switch (type) {
        case 'eye':
            return (
                <svg {...style} viewBox="0 0 24 24">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
            );
        case 'cursor':
            return (
                <svg {...style} viewBox="0 0 24 24">
                    <path d="M3 2l6 20 2-8 8-2-16-10z" />
                </svg>
            );
        case 'check':
            return (
                <svg {...style} viewBox="0 0 24 24">
                    <path d="M5 13l4 4L19 7" />
                </svg>
            );
        case 'chart':
            return (
                <svg {...style} viewBox="0 0 24 24">
                    <path d="M4 19h16M4 15h8M4 11h4M4 7h12" />
                </svg>
            );
        default:
            return null;
    }
};

const StatCard = ({
                      label,
                      value,
                      color,
                      icon,
                      tooltip,
                  }: {
    label: string;
    value: string | number;
    color: string;
    icon: React.ReactNode;
    tooltip: string;
}) => {
    return (
        <div
            role="group"
            aria-label={label}
            title={tooltip}
            tabIndex={0}
            style={{
                flex: '1 1 200px',
                background: '#fff',
                borderRadius: 14,
                border: `1px solid ${color}33`, // subtle border with transparency
                padding: 20,
                textAlign: 'center',
                boxShadow: '0 2px 8px rgba(0,0,0,0.05)',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                cursor: 'default',
                transition: 'box-shadow 0.3s ease',
            }}
            onMouseEnter={e => (e.currentTarget.style.boxShadow = `0 4px 16px ${color}88`)}
            onMouseLeave={e => (e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)')}
        >
            <div
                style={{
                    width: 52,
                    height: 52,
                    borderRadius: '50%',
                    background: `${color}22`, // very subtle background color
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    marginBottom: 14,
                    boxShadow: `inset 0 0 6px ${color}44`, // faint glow inside
                    color,
                    fontSize: 24,
                    lineHeight: '24px',
                }}
            >
                {icon}
            </div>
            <div style={{ fontSize: 14, color: '#6b7280', fontWeight: 600, marginBottom: 10 }}>{label}</div>
            <div style={{ fontSize: 28, fontWeight: 700, color: '#111827', wordBreak: 'break-word' }}>{value}</div>
        </div>
    );
};

export default function RecommendationStats({ shop, showToast }: RecommendationStatsProps) {
    const [stats, setStats] = useState<StatsData | null>(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!shop) {
            setStats(null);
            return;
        }
        setLoading(true);
        axios.get('https://shopifybackend.bcstdr.site/api/recommendation-stats', { params: { shop } })
            .then(res => setStats(res.data))
            .catch(() => showToast('Failed to load stats'))
            .finally(() => setLoading(false));
    }, [shop, showToast]);

    if (loading && !stats) {
        return (
            <div style={{ display: 'flex', gap: 16, marginBottom: 20 }}>
                {Array.from({ length: 4 }).map((_, i) => (
                    <div
                        key={i}
                        style={{
                            flex: '1 1 200px',
                            height: 140,
                            borderRadius: 14,
                            background:
                                'linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 37%, #f0f0f0 63%)',
                            animation: 'shimmer 1.8s infinite',
                        }}
                    />
                ))}
                <style>{`
          @keyframes shimmer {
            0% {
              background-position: -200px 0;
            }
            100% {
              background-position: 200px 0;
            }
          }
        `}</style>
            </div>
        );
    }

    if (!stats) return null;

    const ctr = stats.total_clicks > 0 ? ((stats.total_conversions / stats.total_clicks) * 100).toFixed(2) + '%' : '0.00%';

    const metrics = [
        { label: 'Total Impressions', value: stats.total_impressions.toLocaleString(), color: '#3b82f6', icon: <Icon type="eye" /> , tooltip: 'Number of times upsell recommendations were shown.' },
        { label: 'Total Clicks', value: stats.total_clicks.toLocaleString(), color: '#8b5cf6', icon: <Icon type="cursor" />, tooltip: 'Number of times upsell products were clicked.' },
        { label: 'Total Conversions', value: stats.total_conversions.toLocaleString(), color: '#10b981', icon: <Icon type="check" />, tooltip: 'Number of orders from upsell recommendations.' },
        { label: 'Click-to-Conversion Rate', value: ctr, color: '#ef4444', icon: <Icon type="chart" />, tooltip: 'The percentage of clicks that led to orders.' },
    ];

    return (
        <div style={{ display: 'flex', gap: 20, marginBottom: 20, flexWrap: 'wrap', justifyContent: 'space-evenly' }}>
            {metrics.map(m => (
                <StatCard key={m.label} {...m} />
            ))}
        </div>
    );
}
