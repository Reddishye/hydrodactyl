import { cn } from '@/lib/utils';

describe('@/lib/utils.ts', () => {
    describe('cn()', () => {
        it('should merge class names', () => {
            expect(cn('px-4', 'py-2')).toBe('px-4 py-2');
        });

        it('should handle conditional classes', () => {
            expect(cn('base', false, 'visible')).toBe('base visible');
        });

        it('should resolve tailwind conflicts', () => {
            expect(cn('px-4', 'px-6')).toBe('px-6');
        });

        it('should handle class-variance-authority objects', () => {
            expect(cn('px-4', 'py-2', 'rounded')).toBe('px-4 py-2 rounded');
        });

        it('should return empty string for no inputs', () => {
            expect(cn()).toBe('');
        });
    });
});
