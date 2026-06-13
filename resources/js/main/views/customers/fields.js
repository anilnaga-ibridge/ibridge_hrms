import { ref } from "vue";
import { useI18n } from "vue-i18n";

const fields = () => {
    const url = "customers?fields=id,xid,name,email,phone,website,tax_number,address";
    const addEditUrl = "customers";
    const { t } = useI18n();

    const initData = {
        name: "",
        email: "",
        phone: "",
        website: "",
        tax_number: "",
        address: "",
    };

    const columns = ref([
        {
            title: t("customer.name") || "Customer Name",
            dataIndex: "name",
        },
        {
            title: t("customer.email") || "Email",
            dataIndex: "email",
        },
        {
            title: t("customer.phone") || "Phone",
            dataIndex: "phone",
        },
        {
            title: t("customer.website") || "Website",
            dataIndex: "website",
        },
        {
            title: t("customer.tax_number") || "Tax Number",
            dataIndex: "tax_number",
        },
    ]);

    const filterableColumns = [
        {
            key: "name",
            value: t("customer.name") || "Name",
        },
        {
            key: "email",
            value: t("customer.email") || "Email",
        },
        {
            key: "phone",
            value: t("customer.phone") || "Phone",
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
