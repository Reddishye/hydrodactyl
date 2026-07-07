import { type DependencyList, useMemo } from 'react';

import { useDeepMemoize } from '@/plugins/useDeepMemoize';

export const useDeepCompareMemo = <T>(callback: () => T, dependencies: DependencyList) =>
    // biome-ignore lint/correctness/useExhaustiveDependencies: deep comparison is intentional
    useMemo(callback, useDeepMemoize(dependencies));
