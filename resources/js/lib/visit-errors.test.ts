import { describe, expect, it } from 'vite-plus/test';
import { failedRequestMessage } from '@/lib/visit-errors';

describe('failedRequestMessage', () => {
    it('explains a clash with another user', () => {
        expect(failedRequestMessage(404)).toContain('no longer exists');
    });

    it('explains an expired session', () => {
        expect(failedRequestMessage(419)).toContain('session expired');
    });

    it('falls back to a generic message', () => {
        expect(failedRequestMessage(500)).toBe(
            'Something went wrong. Please try again.',
        );
    });
});
