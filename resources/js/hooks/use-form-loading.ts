import { useState } from 'react';

interface FormLoadingOptions {
    loadingText?: string;
    defaultText?: string;
}

export function useFormLoading(options: FormLoadingOptions = {}) {
    const { loadingText = 'Loading...', defaultText = 'Submit' } = options;
    const [isLoading, setIsLoading] = useState(false);

    const getButtonText = (customLoadingText?: string, customDefaultText?: string) => {
        if (isLoading) {
            return customLoadingText || loadingText;
        }
        return customDefaultText || defaultText;
    };

    const withLoading = async <T>(asyncFn: () => Promise<T>): Promise<T> => {
        setIsLoading(true);
        try {
            return await asyncFn();
        } finally {
            setIsLoading(false);
        }
    };

    return {
        isLoading,
        setIsLoading,
        getButtonText,
        withLoading,
    };
}
