import { useEffect, useState } from "react";

export function useReaderFeed(fetchEntries) {
    const [entries, setEntries] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);
    const [request, setRequest] = useState(0);

    useEffect(() => {
        let stale = false;
        fetchEntries().then((response) => {
            if (!stale) setEntries(Array.isArray(response) ? response : response.data || []);
        }).catch(() => {
            if (!stale) setError(true);
        }).finally(() => {
            if (!stale) setLoading(false);
        });
        return () => { stale = true; };
    }, [fetchEntries, request]);

    function reload() {
        setLoading(true);
        setError(false);
        setRequest((request) => request + 1);
    }

    return { entries, loading, error, reload };
}
