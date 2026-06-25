<template>
    <a-drawer
        :title="pageTitle"
        :width="720"
        :open="visible"
        :body-style="{ paddingBottom: '80px' }"
        :footer-style="{ textAlign: 'right' }"
    >
        <a-row :gutter="16" v-if="data.generate && data.generate.description">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
                <div style="border-top: 1px solid #f0f0f0; padding-top: 20px; margin-top: 20px;">
                    <h3 style="font-weight: bold; margin-bottom: 15px;">{{ $t('common.preview') }}</h3>
                    <div style="width: 500px; height: 354px; overflow: hidden; border: 1px solid #d9d9d9; border-radius: 4px; background: #fff; margin: 0 auto;">
                        <div class="ql-editor" style="transform: scale(0.445); transform-origin: top left; width: 1123px !important; height: 794px !important; max-width: none !important; max-height: none !important; padding: 0 !important; overflow: hidden !important; white-space: normal !important;" v-html="parsedDescription(data.generate.description)"></div>
                    </div>
                </div>
            </a-col>
        </a-row>

        <template #footer>
            <a-space>
                <PdfDownload
                    v-if="data.generate && data.generate.xid"
                    :fileName="data.generate.title || 'certificate'"
                    :url="`generate-pdf/${data.generate.xid}`"
                    :title="$t('appreciation.appreciation_letter')"
                />
                <a-button key="back" @click="onClose">
                    {{ $t("common.cancel") }}
                </a-button>
            </a-space>
        </template>
    </a-drawer>
</template>

<script>
import { defineComponent } from "vue";
import common from "../../../../common/composable/common";
import PdfDownload from "@/main/components/pdf/PdfDownload.vue";

export default defineComponent({
    components: { PdfDownload },
    props: ["data", "visible", "pageTitle"],
    setup(props, { emit }) {
        const { appSetting } = common();

        const onClose = () => {
            emit("close");
        };

        const escapeHtml = (string) => {
            if (!string) return '';
            return string
                .toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        const parsedDescription = (description) => {
            if (!description) return "";
            let html = description;
            const logoUrl = escapeHtml(appSetting.value?.light_logo_url);
            const logoHtml = logoUrl ? `<img src="${logoUrl}" style="max-height: 50px; max-width: 180px; display: block; margin: 0 auto; object-fit: contain;" />` : '';
            const companyName = escapeHtml(appSetting.value?.name || '');
            
            html = html.replaceAll('##COMPANY_LOGO##', logoHtml);
            html = html.replaceAll('##COMPANY_NAME##', companyName);
            return html;
        };

        return {
            onClose,
            parsedDescription,
        };
    },
});
</script>
