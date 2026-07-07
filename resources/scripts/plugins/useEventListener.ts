import { useEffect, useRef } from 'react';

export default (
    eventName: string,
    handler: (e: Event | CustomEvent) => void,
    options?: boolean | EventListenerOptions,
) => {
    const savedHandler = useRef<((e: Event | CustomEvent) => void) | null>(null);

    useEffect(() => {
        savedHandler.current = handler;
    }, [handler]);

    useEffect(() => {
        const isSupported = window?.addEventListener;
        if (!isSupported) return;

        const eventListener = (event: Event) => savedHandler.current?.(event);
        window.addEventListener(eventName, eventListener, options);
        return () => {
            window.removeEventListener(eventName, eventListener);
        };
    }, [eventName, options]);
};
