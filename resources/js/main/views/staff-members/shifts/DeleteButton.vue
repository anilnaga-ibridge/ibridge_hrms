<template>
    <div v-if="permsArray.includes('shifts_delete') || permsArray.includes('admin')">
        <a-tooltip :title="$t('common.delete')">
            <a-button @click="showDeleteConfirm" class="ml-5 no-border-radius" type="default" danger>
                <template #icon> <DeleteOutlined /> </template>
            </a-button>
        </a-tooltip>
    </div>
</template>

<script>
import { defineComponent, createVNode } from "vue";
import { DeleteOutlined, ExclamationCircleOutlined } from "@ant-design/icons-vue";
import { Modal, notification } from "ant-design-vue";
import { useI18n } from "vue-i18n";
import common from "../../../../common/composable/common";

export default defineComponent({
    props: {
        id: {
            required: true,
        },
    },
    emits: ["onDeleteSuccess"],
    components: {
        DeleteOutlined,
    },
    setup(props, { emit }) {
        const { permsArray, appSetting } = common();
        const { t } = useI18n();

        const showDeleteConfirm = () => {
            Modal.confirm({
                title: t("common.delete") + "?",
                icon: createVNode(ExclamationCircleOutlined),
                content: t("shift.delete_message"),
                centered: true,
                okText: t("common.yes"),
                okType: "danger",
                cancelText: t("common.no"),
                onOk() {
                    return axiosAdmin
                        .delete(`shifts/${props.id}`)
                        .then((successResponse) => {
                            notification.success({
                                message: t("common.success"),
                                description: t("shift.deleted"),
                                placement: appSetting.value.rtl ? "bottomLeft" : "bottomRight",
                            });
                            emit("onDeleteSuccess", props.id);
                        })
                        .catch(() => {});
                },
                onCancel() {},
            });
        };

        return {
            permsArray,
            showDeleteConfirm,
        };
    },
});
</script>
