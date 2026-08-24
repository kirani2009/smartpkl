import { useState, useEffect, useCallback } from 'react';
import api from '../api/api';

/**
 * Generic data-fetching hook.
 * @param {string} url — API endpoint (relative to /api)
 * @param {object} options — { params, enabled (default true) }
 */
export function useFetch(url, options = {}) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const { params = {}, enabled = true } = options;

  const fetchData = useCallback(async () => {
    if (!enabled || !url) return;
    setLoading(true);
    setError(null);
    try {
      const { data: res } = await api.get(url, { params });
      if (res.success) {
        setData(res.data);
      } else {
        setError(res.message);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Terjadi kesalahan.');
    } finally {
      setLoading(false);
    }
  }, [url, JSON.stringify(params), enabled]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  return { data, loading, error, refetch: fetchData };
}

/**
 * Generic mutation hook for POST/PUT/DELETE.
 */
export function useMutation() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const mutate = useCallback(async (method, url, payload) => {
    setLoading(true);
    setError(null);
    try {
      const { data: res } = await api[method](url, payload);
      return res;
    } catch (err) {
      const errMsg = err.response?.data?.message || 'Terjadi kesalahan.';
      setError(errMsg);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  return { mutate, loading, error };
}
