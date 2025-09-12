import type { ApiResponse } from '@/types';
import axios, { type AxiosResponse } from 'axios';

export class ApiError extends Error {
    constructor(
        message: string,
        public statusCode: number,
        public errors?: Record<string, string[]> | null,
        public response?: AxiosResponse,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

export function handleApiResponse<T>(response: AxiosResponse<ApiResponse<T>>): T {
    if (response.data.success) {
        return response.data.data;
    }

    throw new ApiError(response.data.message, response.status, response.data.errors, response);
}

export function handleApiError(error: unknown): ApiError {
    if (error instanceof ApiError) {
        return error;
    }

    if (axios.isAxiosError(error)) {
        const response = error.response;
        if (response?.data?.message) {
            return new ApiError(response.data.message, response.status || 500, response.data.errors || null, response);
        }

        return new ApiError(error.message || 'Network error occurred', response?.status || 500, null, response);
    }

    return new ApiError(error instanceof Error ? error.message : 'An unexpected error occurred', 500);
}

export async function apiRequest<T>(requestPromise: Promise<AxiosResponse<ApiResponse<T>>>): Promise<T> {
    try {
        const response = await requestPromise;
        return handleApiResponse(response);
    } catch (error) {
        throw handleApiError(error);
    }
}
