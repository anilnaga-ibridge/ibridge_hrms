<template>
    <a-modal
        :open="visible"
        :closable="false"
        :centered="true"
        :title="pageTitle"
        width="600px"
        @ok="onSubmit"
    >
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('customer.name')"
                        name="name"
                        :help="rules.name ? rules.name.message : null"
                        :validateStatus="rules.name ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.name"
                            :placeholder="$t('common.placeholder_default_text', [$t('customer.name')])"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('customer.email')"
                        name="email"
                        :help="rules.email ? rules.email.message : null"
                        :validateStatus="rules.email ? 'error' : null"
                    >
                        <a-input
                            v-model:value="formData.email"
                            :placeholder="$t('common.placeholder_default_text', [$t('customer.email')])"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('customer.phone')"
                        name="phone"
                        :help="rules.phone ? rules.phone.message : null"
                        :validateStatus="rules.phone ? 'error' : null"
                    >
                        <a-input
                            v-model:value="formData.phone"
                            :placeholder="$t('common.placeholder_default_text', [$t('customer.phone')])"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('customer.website')"
                        name="website"
                        :help="rules.website ? rules.website.message : null"
                        :validateStatus="rules.website ? 'error' : null"
                    >
                        <a-input
                            v-model:value="formData.website"
                            :placeholder="$t('common.placeholder_default_text', [$t('customer.website')])"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item
                        :label="$t('customer.tax_number')"
                        name="tax_number"
                        :help="rules.tax_number ? rules.tax_number.message : null"
                        :validateStatus="rules.tax_number ? 'error' : null"
                    >
                        <a-input
                            v-model:value="formData.tax_number"
                            :placeholder="$t('common.placeholder_default_text', [$t('customer.tax_number')])"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :span="24">
                    <a-form-item
                        :label="$t('customer.address')"
                        name="address"
                        :help="rules.address ? rules.address.message : null"
                        :validateStatus="rules.address ? 'error' : null"
                    >
                        <a-textarea
                            v-model:value="formData.address"
                            :rows="4"
                            :placeholder="$t('common.placeholder_default_text', [$t('customer.address')])"
                        />
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>
        <template #footer>
            <a-button
                key="submit"
                type="primary"
                :loading="loading"
                @click="onSubmit"
            >
                <template #icon>
                    <SaveOutlined />
                </template>
                {{ addEditType == 'add' ? $t('common.create') : $t('common.update') }}
            </a-button>
            <a-button key="back" @click="onClose">
                {{ $t("common.cancel") }}
            </a-button>
        </template>
    </a-modal>
</template>

<script>
import { defineComponent } from "vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import apiAdmin from "../../../common/composable/apiAdmin";

export default defineComponent({
    props: [
        "formData",
        "data",
        "visible",
        "url",
        "addEditType",
        "pageTitle",
        "successMessage",
    ],
    components: {
        SaveOutlined,
    },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();

        const onSubmit = () => {
            addEditRequestAdmin({
                url: props.url,
                data: props.formData,
                successMessage: props.successMessage,
                success: (res) => {
                    emit("addEditSuccess", res.xid);
                },
            });
        };

        const onClose = () => {
            rules.value = {};
            emit("closed");
        };

        return {
            loading,
            rules,
            onClose,
            onSubmit,
        };
    },
});
</script>
