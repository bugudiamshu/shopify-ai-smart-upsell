import React from "react";
import { Button, Icon } from "@shopify/polaris";
import {CheckIcon, ProductRemoveIcon, DeleteIcon, EditIcon} from "@shopify/polaris-icons";

interface Props {
    enabled: boolean | number;
    onToggle: () => void;
    onDelete: () => void;
    onEdit: () => void;
}

export function RecommendationActions({ enabled, onToggle, onDelete, onEdit }: Props) {
    return (
        <div style={{ display: 'flex', gap: 8, flexDirection: "column", alignItems: "end" }}>
            <Button
                variant="secondary"
                tone={enabled ? "critical" : "success"}
                icon={enabled ? ProductRemoveIcon : CheckIcon}
                size="slim"
                onClick={onToggle}
            >
                {enabled ? "Deactivate" : "Activate"}
            </Button>

            <Button
                size="slim"
                variant="secondary"
                icon={EditIcon}
                onClick={onEdit}
            >
                Edit
            </Button>

            <Button
                variant="secondary"
                tone="critical"
                size="slim"
                icon={DeleteIcon}
                onClick={onDelete}
            >
                Delete
            </Button>
        </div>
    );
}
