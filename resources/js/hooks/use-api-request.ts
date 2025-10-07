import { ApiError } from '@/utils/api';
import { router } from '@inertiajs/react';
import axios, { type AxiosRequestConfig } from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';
import { route } from 'ziggy-js';

interface UseApiRequestOptions<T> {
    onSuccess?: (data: T) => void;
    onError?: (error: Error) => void;
    onSettled?: () => void;
}

interface ApiRequestParams {
    url: string;
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    data?: unknown;
    config?: AxiosRequestConfig;
}

export function useApiRequest<T>() {
    const [loading, setLoading] = useState(false);

    const execute = async (params: ApiRequestParams, options: UseApiRequestOptions<T> = {}) => {
        const { onSuccess, onError, onSettled } = options;
        const { url, method = 'GET', data, config } = params;

        setLoading(true);

        try {
            let requestPromise;

            switch (method) {
                case 'POST':
                    requestPromise = axios.post<App.Data.ApiData>(url, data, config);
                    break;
                case 'PUT':
                    requestPromise = axios.put<App.Data.ApiData>(url, data, config);
                    break;
                case 'PATCH':
                    requestPromise = axios.patch<App.Data.ApiData>(url, data, config);
                    break;
                case 'DELETE':
                    requestPromise = axios.delete<App.Data.ApiData>(url, { ...config, data });
                    break;
                default:
                    requestPromise = axios.get<App.Data.ApiData>(url, config);
            }

            const response = await requestPromise;
            const apiData = response.data as App.Data.ApiData;

            const responseData = apiData.data as T;
            onSuccess?.(responseData);

            if (apiData.message) {
                toast.success(apiData.message);
            }

            return responseData;
        } catch (error) {
            const apiError = error as ApiError;

            if (apiError.statusCode === 401) {
                const currentPath = window.location.pathname + window.location.search;
                const redirectUrl = route('login', { redirect: encodeURIComponent(currentPath) });
                router.visit(redirectUrl);
                return;
            }

            onError?.(apiError);
            console.error('Error performing API request:', apiError);
            toast.error(apiError.message || 'Something went wrong. Please try again.');
        } finally {
            setLoading(false);
            onSettled?.();
        }
    };

    return {
        loading,
        execute,
    };
}
