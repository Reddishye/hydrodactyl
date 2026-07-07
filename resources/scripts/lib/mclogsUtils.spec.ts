import { debounce, extractRelevantLogs, isCrashLine } from '@/lib/mclogsUtils';

describe('@/lib/mclogsUtils.ts', () => {
    describe('isCrashLine()', () => {
        it('should detect Java OOM error', () => {
            expect(isCrashLine('java.lang.outofmemoryerror')).toBe(true);
        });

        it('should detect Minecraft crash report header', () => {
            expect(isCrashLine('---- minecraft crash report ----')).toBe(true);
        });

        it('should detect segmentation fault', () => {
            expect(isCrashLine('segmentation fault')).toBe(true);
        });

        it('should detect fatal error', () => {
            expect(isCrashLine('A fatal error has been detected by the Java Runtime Environment')).toBe(true);
        });

        it('should detect crash exit codes via regex', () => {
            expect(isCrashLine('exit code: 1')).toBe(true);
            expect(isCrashLine('exit code: 137')).toBe(true);
            expect(isCrashLine('exit code: 139')).toBe(true);
            expect(isCrashLine('exit code 143')).toBe(true);
        });

        it('should detect hs_err_pid files', () => {
            expect(isCrashLine('hs_err_pid12345.log')).toBe(true);
        });

        it('should detect OOM killer', () => {
            expect(isCrashLine('oom-killer')).toBe(true);
            expect(isCrashLine('oomkiller')).toBe(true);
        });

        it('should return false for normal log lines', () => {
            expect(isCrashLine('[12:00:00] [Server thread/INFO]: Done')).toBe(false);
            expect(isCrashLine('')).toBe(false);
        });
    });

    describe('extractRelevantLogs()', () => {
        it('should return empty string for empty logs', () => {
            expect(extractRelevantLogs([])).toBe('');
        });

        it('should return last maxLines when no crash found', () => {
            const logs = Array.from({ length: 200 }, (_, i) => `line ${i + 1}`);
            const result = extractRelevantLogs(logs);
            expect(result).toBe(logs.slice(-150).join('\n'));
        });

        it('should extract context around a crash line', () => {
            const logs = Array.from({ length: 200 }, (_, i) => `line ${i + 1}`);
            logs[100] = '---- minecraft crash report ----';
            const result = extractRelevantLogs(logs, 150, 75, 75);
            const lines = result.split('\n');
            expect(lines).toContain('---- minecraft crash report ----');
        });

        it('should respect maxLines limit', () => {
            const logs = Array.from({ length: 400 }, (_, i) => `line ${i + 1}`);
            logs[200] = 'fatal error';
            const result = extractRelevantLogs(logs, 100);
            const lines = result.split('\n');
            expect(lines.length).toBeLessThanOrEqual(100);
        });

        it('should include crash line in output', () => {
            const logs = ['normal', 'crash report', 'normal'];
            const result = extractRelevantLogs(logs);
            expect(result).toContain('crash report');
        });
    });

    describe('debounce()', () => {
        it('should debounce function calls', async () => {
            let callCount = 0;
            const fn = debounce(() => {
                callCount++;
            }, 50);

            fn();
            fn();
            fn();

            expect(callCount).toBe(0);

            await new Promise((resolve) => setTimeout(resolve, 100));

            expect(callCount).toBe(1);
        });

        it('should call with the latest arguments', async () => {
            let lastArgs: unknown[] = [];
            const fn = debounce((...args: unknown[]) => {
                lastArgs = args;
            }, 50);

            fn('a');
            fn('b');

            await new Promise((resolve) => setTimeout(resolve, 100));

            expect(lastArgs).toEqual(['b']);
        });
    });
});
