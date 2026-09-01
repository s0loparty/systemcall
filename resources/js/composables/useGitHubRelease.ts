// composables/useGitHubRelease.ts
import { type Ref, ref } from 'vue';

interface GitHubAsset {
    name: string;
    browser_download_url: string;
}

interface GitHubRelease {
    tag_name: string;
    name: string;
    assets: GitHubAsset[];
}

export interface UseGitHubReleaseOptions {
    /** Имя репозитория в формате 'owner/repo' */
    repo: string;
    /** Фильтр для поиска APK файла */
    apkFilter?: (asset: GitHubAsset) => boolean;
    /** Автоматически загружать данные при создании */
    autoFetch?: boolean;
    /** Заголовки для запроса (например, для авторизации) */
    headers?: HeadersInit;
}

export function useGitHubRelease(options: UseGitHubReleaseOptions) {
    const {
        repo,
        apkFilter = (asset) =>
            asset.name.endsWith('.apk') && asset.name.includes('arm64-release'),
        autoFetch = true,
        headers = {},
    } = options;

    // Состояния
    const release = ref<GitHubRelease | null>(null);
    const apkUrl = ref<string | null>(null);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Методы
    const fetchLatestRelease = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            const url = `https://api.github.com/repos/${repo}/releases/latest`;
            const response = await fetch(url, { headers });

            if (!response.ok) {
                throw new Error(
                    `GitHub API error: ${response.status} ${response.statusText}`,
                );
            }

            const data: GitHubRelease = await response.json();
            release.value = data;

            console.log(data);

            // Ищем APK по фильтру
            const asset = data.assets.find(apkFilter);

            if (!asset) {
                throw new Error('APK file not found in latest release');
            }

            apkUrl.value = asset.browser_download_url;

            return { release: data, apkUrl: asset.browser_download_url };
        } catch (err) {
            error.value =
                err instanceof Error ? err.message : 'Unknown error occurred';

            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const downloadApk = (filename?: string) => {
        if (!apkUrl.value) {
            throw new Error(
                'APK URL is not available. Call fetchLatestRelease() first.',
            );
        }

        const link = document.createElement('a');
        link.href = apkUrl.value;
        link.download =
            filename || `systemcall-${release.value?.tag_name || 'latest'}.apk`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    const reset = () => {
        release.value = null;
        apkUrl.value = null;
        error.value = null;
        isLoading.value = false;
    };

    // Автоматическая загрузка
    if (autoFetch) {
        fetchLatestRelease();
    }

    return {
        // Состояния
        release: release as Ref<GitHubRelease | null>,
        apkUrl: apkUrl as Ref<string | null>,
        isLoading: isLoading as Ref<boolean>,
        error: error as Ref<string | null>,

        // Методы
        fetchLatestRelease,
        downloadApk,
        reset,

        // Утилиты
        isReady: () => !!apkUrl.value && !error.value,
        hasError: () => !!error.value,
    };
}
