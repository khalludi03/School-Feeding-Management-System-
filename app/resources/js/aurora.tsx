import React from 'react';
import { createRoot } from 'react-dom/client';
import { AuroraBackground } from '@/components/ui/aurora-background';

const target = document.getElementById('aurora-root');

if (target) {
    createRoot(target).render(
        <AuroraBackground className="h-full min-h-screen" showRadialGradient>
            <></>
        </AuroraBackground>,
    );
}
