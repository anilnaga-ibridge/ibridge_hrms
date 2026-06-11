<template>
    <div v-if="permsArray.includes('shifts_edit') || permsArray.includes('admin')">
        <a-tooltip :title="$t('common.edit')">
            <a-button @click="showEdit" class="ml-5 no-border-radius" type="default" :loading="btnLoading">
                <template #icon> <EditOutlined /> </template>
            </a-button>
        </a-tooltip>

        <AddEdit
            addEditType="edit"
            :visible="visible"
            :url="addEditUrl + '/' + id"
            @addEditSuccess="addEditSuccess"
            @closed="onClose"
            :formData="formData"
            :data="formData"
            :pageTitle="$t('shift.edit')"
            :successMessage="$t('shift.updated')"
        />
    </div>
</template>

<script>
import { defineComponent, ref } from "vue";
import { EditOutlined } from "@ant-design/icons-vue";
import fields from "./fields";
import AddEdit from "./AddEdit.vue";
import common from "../../../../common/composable/common";

export default defineComponent({
    props: {
        id: {
            required: true,
        },
    },
    emits: ["onEditSuccess"],
    components: {
        EditOutlined,
        AddEdit,
    },
    setup(props, { emit }) {
        const { permsArray } = common();
        const { initData, addEditUrl } = fields();
        const visible = ref(false);
        const btnLoading = ref(false);
        const formData = ref({ ...initData });

        const showEdit = () => {
            btnLoading.value = true;
            axiosAdmin.get(`${addEditUrl}/${props.id}`)
                .then((response) => {
                    const data = response.data;
                    formData.value = {
                        ...data,
                        _method: "PUT",
                    };
                    visible.value = true;
                })
                .finally(() => {
                    btnLoading.value = false;
                });
        };

        const addEditSuccess = (xid) => {
            visible.value = false;
            emit("onEditSuccess", xid);
        };

        const onClose = () => {
            visible.value = false;
        };

        return {
            permsArray,
            visible,
            btnLoading,
            addEditUrl,
            formData,
            addEditSuccess,
            onClose,
            showEdit,
        };
    },
});
</script>
