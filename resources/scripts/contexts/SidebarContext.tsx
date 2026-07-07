import type React from 'react';
import { createContext, type ReactNode, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { usePersistedState } from '@/plugins/usePersistedState';

export const SIDEBAR_WIDTH = {
    MINIMIZED: 128,
    REGULAR: 300,
} as const;

interface SidebarContextType {
    isMinimized: boolean;
    toggleMinimized: () => void;
    isMobileOpen: boolean;
    setMobileOpen: (open: boolean) => void;
    toggleMobile: () => void;
}

const SidebarContext = createContext<SidebarContextType | undefined>(undefined);

export const SidebarProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
    const [isMinimized, setIsMinimized] = usePersistedState('sidebar:minimized', true);
    const [isMobileOpen, setMobileOpen] = useState(false);

    const toggleMinimized = useCallback(() => {
        setIsMinimized((prev) => {
            const newValue = !(prev ?? true);
            document.body.setAttribute('data-sidebar-minimized', String(newValue));
            return newValue;
        });
    }, [setIsMinimized]);

    const toggleMobile = useCallback(() => {
        setMobileOpen((prev) => !prev);
    }, []);

    // init data attribute
    useEffect(() => {
        document.body.setAttribute('data-sidebar-minimized', String(isMinimized ?? true));
    }, [isMinimized]);

    const contextValue = useMemo(
        () => ({
            isMinimized: isMinimized ?? true,
            toggleMinimized,
            isMobileOpen,
            setMobileOpen,
            toggleMobile,
        }),
        [isMinimized, isMobileOpen, toggleMinimized, toggleMobile],
    );

    return <SidebarContext.Provider value={contextValue}>{children}</SidebarContext.Provider>;
};

export const useSidebar = () => {
    const context = useContext(SidebarContext);
    if (context === undefined) {
        throw new Error('useSidebar must be used within a SidebarProvider');
    }
    return context;
};
