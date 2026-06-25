import { ref } from "vue";
import { useI18n } from "vue-i18n";

const fields = () => {
    const url = "rotational-teams?fields=id,xid,name,rotation_order,is_active";
    const addEditUrl = "rotational-teams";
    const { t } = useI18n();

    const initData = {
        name: "",
        rotation_order: 0,
    };

    const columns = ref([
        {
            title: t("common.name"),
            dataIndex: "name",
        },
        {
            title: "Rotation Order",
            dataIndex: "rotation_order",
        },
    ]);

    const filterableColumns = [
        {
            key: "name",
            value: t("common.name"),
        },
    ];

    return {
        addEditUrl,
        initData,
        columns,
        filterableColumns,
    };
};

export default fields;
