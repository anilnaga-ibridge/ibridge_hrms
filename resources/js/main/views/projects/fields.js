import { ref } from "vue";
import { useI18n } from "vue-i18n";

const fields = () => {
    const url = "projects?fields=id,xid,name,status,start_date,deadline,description,members,member_details,customer,customer_id,calculate_progress,progress,billing_type,total_rate,estimated_hours,tags,send_email";
    const addEditUrl = "projects";
    const { t } = useI18n();

    const initData = {
        name: "",
        customer: "",
        customer_id: undefined,
        calculate_progress: false,
        progress: 0,
        billing_type: "fixed_rate",
        total_rate: 0,
        estimated_hours: 0,
        status: "in_progress",
        members: [],
        start_date: undefined,
        deadline: undefined,
        tags: [],
        description: "",
        send_email: false,
    };

    const columns = ref([
        {
            title: t("project.name"),
            dataIndex: "name",
        },
        {
            title: t("project.customer") || "Customer",
            dataIndex: "customer",
        },
        {
            title: t("project.status"),
            dataIndex: "status",
        },
        {
            title: t("project.progress") || "Progress",
            dataIndex: "progress",
        },
        {
            title: t("project.billing_type") || "Billing Type",
            dataIndex: "billing_type",
        },
        {
            title: t("project.start_date"),
            dataIndex: "start_date",
        },
        {
            title: t("project.deadline"),
            dataIndex: "deadline",
        },
        {
            title: t("project.members"),
            dataIndex: "members",
        },
    ]);

    const filterableColumns = [
        {
            key: "name",
            value: t("project.name"),
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
