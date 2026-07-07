import { type DependencyList, type EffectCallback, useEffect } from 'react';

import { useDeepMemoize } from './useDeepMemoize';

export const useDeepCompareEffect = (callback: EffectCallback, dependencies: DependencyList) =>
    // biome-ignore lint/correctness/useExhaustiveDependencies: deep comparison is intentional
    useEffect(callback, useDeepMemoize(dependencies));
