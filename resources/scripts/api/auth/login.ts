import http from '@/api/http';

interface LoginData {
    user: string;
    password: string;
    [key: string]: unknown; // Allow additional fields like captcha responses
}

interface LoginResponse {
    complete: boolean;
    intended?: string;
    confirmationToken?: string;
    error?: string;
}

export default async (data: LoginData): Promise<LoginResponse> => {
    try {
        await http.get('/sanctum/csrf-cookie');

        // Pass through all data including captcha responses
        const payload: Record<string, unknown> = {
            ...data,
        };

        const response = await http.post('/auth/login', payload);

        if (!response.data || typeof response.data !== 'object') {
            throw new Error('Invalid server response format');
        }

        return {
            complete: response.data.complete ?? response.data.data?.complete ?? false,
            intended: response.data.intended ?? response.data.data?.intended,
            confirmationToken:
                response.data.confirmationToken ??
                response.data.data?.confirmation_token ??
                response.data.data?.confirmationToken,
            error: response.data.error ?? response.data.message,
        };
    } catch (error: unknown) {
        const err = error as {
            response?: {
                data?: { error?: string; message?: string; errors?: Array<{ detail?: string; code?: string }> };
                status?: number;
            };
            message?: string;
        };
        const loginError = new Error(
            err.response?.data?.error ??
                err.response?.data?.message ??
                err.message ??
                'Login failed. Please try again.',
        ) as Error & { response?: unknown; detail?: string; code?: string };

        loginError.response = err.response;
        loginError.detail = err.response?.data?.errors?.[0]?.detail;
        loginError.code = err.response?.data?.errors?.[0]?.code;

        console.error('Login API Error:', {
            status: err.response?.status,
            data: err.response?.data,
            detail: loginError.detail,
            message: loginError.message,
        });

        throw loginError;
    }
};
