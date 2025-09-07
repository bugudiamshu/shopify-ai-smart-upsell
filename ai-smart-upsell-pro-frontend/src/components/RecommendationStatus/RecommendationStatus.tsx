import React from "react";
import { Badge } from "@shopify/polaris";

export function RecommendationStatus({ enabled }: { enabled: boolean | number }) {
    return (
        <Badge tone={enabled ? "success" : "info"}>
            {enabled ? "Active" : "Inactive"}
        </Badge>
    );
}
