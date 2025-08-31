import { apiRequest } from '@/utils/api';
import axios, { type AxiosRequestConfig } from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

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
                    requestPromise = axios.post(url, data, config);
                    break;
                case 'PUT':
                    requestPromise = axios.put(url, data, config);
                    break;
                case 'PATCH':
                    requestPromise = axios.patch(url, data, config);
                    break;
                case 'DELETE':
                    requestPromise = axios.delete(url, { ...config, data });
                    break;
                default:
                    requestPromise = axios.get(url, config);
            }

            const responseData = await apiRequest<T>(requestPromise);
            onSuccess?.(responseData);
            return responseData;
        } catch (error) {
            onError?.(error as Error);
            toast.error((error as Error).message || 'Something went wrong. Please try again.');
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
