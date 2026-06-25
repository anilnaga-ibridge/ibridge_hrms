<template>
    <a-card
        v-if="todayBirthdays && todayBirthdays.length > 0"
        class="birthday-wish-card"
        :bodyStyle="{ padding: '0' }"
    >
        <!-- Vintage Parchment Background Frame -->
        <div class="certificate-frame">
            <!-- Confetti -->
            <div class="confetti">
                <span v-for="n in 15" :key="n" class="confetti-piece" :style="getConfettiStyle(n)"></span>
            </div>

            <!-- Corner Flourishes -->
            <!-- Top-Left -->
            <svg class="corner-flourish tl" width="80" height="80" viewBox="0 0 100 100">
                <path d="M 0,0 C 30,5 50,15 60,35 C 45,25 30,25 25,40 C 20,30 5,20 0,0 Z" fill="#5d4037" opacity="0.85" />
                <path d="M 0,0 C 5,30 15,50 35,60 C 25,45 25,30 40,25 C 30,20 20,5 0,0 Z" fill="#5d4037" opacity="0.85" />
                <circle cx="18" cy="18" r="3" fill="#d4af37" />
                <circle cx="32" cy="32" r="2.5" fill="#d4af37" />
            </svg>
            <!-- Top-Right -->
            <svg class="corner-flourish tr" width="80" height="80" viewBox="0 0 100 100">
                <path d="M 0,0 C 30,5 50,15 60,35 C 45,25 30,25 25,40 C 20,30 5,20 0,0 Z" fill="#5d4037" opacity="0.85" />
                <path d="M 0,0 C 5,30 15,50 35,60 C 25,45 25,30 40,25 C 30,20 20,5 0,0 Z" fill="#5d4037" opacity="0.85" />
                <circle cx="18" cy="18" r="3" fill="#d4af37" />
                <circle cx="32" cy="32" r="2.5" fill="#d4af37" />
            </svg>
            <!-- Bottom-Right -->
            <svg class="corner-flourish br" width="80" height="80" viewBox="0 0 100 100">
                <path d="M 0,0 C 30,5 50,15 60,35 C 45,25 30,25 25,40 C 20,30 5,20 0,0 Z" fill="#5d4037" opacity="0.85" />
                <path d="M 0,0 C 5,30 15,50 35,60 C 25,45 25,30 40,25 C 30,20 20,5 0,0 Z" fill="#5d4037" opacity="0.85" />
                <circle cx="18" cy="18" r="3" fill="#d4af37" />
                <circle cx="32" cy="32" r="2.5" fill="#d4af37" />
            </svg>

            <!-- Inner double border border-inlay -->
            <div class="border-inlay">
                <div class="certificate-content">
                    <!-- Company Logo -->
                    <div v-if="appSetting && appSetting.light_logo_url" class="company-logo-container">
                        <img :src="appSetting.light_logo_url" alt="Company Logo" class="company-logo" />
                    </div>

                    <!-- Header -->
                    <h1 class="cert-title">{{ $t('hrm_dashboard.birthday_certificate') }}</h1>
                    <p class="cert-subtitle">{{ $t('hrm_dashboard.this_certifies_that') }}</p>

                    <!-- Recipient Info -->
                    <div v-for="(person, index) in todayBirthdays" :key="index" class="recipient-container">
                        <div class="avatar-cameo">
                            <a-avatar :size="90" :src="person.image_url" class="birthday-avatar" />
                        </div>
                        <h2 class="recipient-name">{{ person.name }}</h2>
                        <div class="name-underline"></div>
                        <p class="recipient-designation">{{ person.designation }}</p>
                        <p v-if="person.age && person.age > 0" class="recipient-age">
                            {{ $t('hrm_dashboard.celebrating_years_of_life', { age: person.age }) }}
                        </p>
                    </div>

                    <!-- Celebration Date Statement -->
                    <p class="celebration-date">
                        {{ $t('hrm_dashboard.birthday_celebrated_on', { date: formattedTodayDate }) }}
                    </p>

                    <!-- Wish Message (The database translation) -->
                    <div class="wish-text-container">
                        <p class="wish-message">{{ getWishMessage(todayBirthdays) }}</p>
                    </div>

                    <!-- Footer Signature Area -->
                    <div class="certificate-footer">
                        <!-- Left: Watch & Flowers -->
                        <div class="watch-container">
                            <svg viewBox="0 0 120 120" class="pocket-watch-svg">
                                <g class="vintage-flower">
                                    <path d="M 25,95 C 15,90 10,75 22,70 C 26,75 22,88 25,95 Z" fill="#8a9a86" opacity="0.9" />
                                    <path d="M 30,100 C 20,105 15,115 28,118 C 32,112 28,102 30,100 Z" fill="#8a9a86" opacity="0.9" />
                                    <path d="M 35,90 Q 30,85 25,90 Q 20,95 28,100 Q 20,105 25,110 Q 30,115 35,110 Q 40,115 45,110 Q 50,105 42,100 Q 50,95 45,90 Q 40,85 35,90 Z" fill="#ebdcb9" stroke="#795548" stroke-width="0.8" />
                                    <circle cx="35" cy="100" r="4" fill="#d4af37" stroke="#795548" stroke-width="0.5" />
                                </g>
                                <circle cx="75" cy="75" r="32" fill="url(#gold-gradient)" stroke="#5d4037" stroke-width="2.5" />
                                <circle cx="75" cy="75" r="28" fill="#ebdcb9" stroke="#8a6d3b" stroke-width="1" />
                                <circle cx="75" cy="75" r="24" fill="#fffef9" stroke="#795548" stroke-width="0.8" />
                                <g transform="translate(75, 43)">
                                    <rect x="-3" y="-5" width="6" height="5" fill="#a8853b" stroke="#5d4037" stroke-width="1" />
                                    <ellipse cx="0" cy="-6" rx="5" ry="2.5" fill="#d4af37" stroke="#5d4037" stroke-width="1" />
                                    <path d="M -7,-6 C -7,-15 7,-15 7,-6" fill="none" stroke="#5d4037" stroke-width="2" />
                                </g>
                                <line x1="75" y1="75" x2="75" y2="58" stroke="#2c1e18" stroke-width="2" stroke-linecap="round" />
                                <line x1="75" y1="75" x2="88" y2="70" stroke="#2c1e18" stroke-width="1.5" stroke-linecap="round" />
                                <circle cx="75" cy="75" r="2.5" fill="#2c1e18" />
                                <text x="75" y="57" font-family="'Times New Roman', Georgia, serif" font-size="5" text-anchor="middle" fill="#5d4037" font-weight="bold">XII</text>
                                <text x="75" y="97" font-family="'Times New Roman', Georgia, serif" font-size="5" text-anchor="middle" fill="#5d4037" font-weight="bold">VI</text>
                                <text x="55" y="77" font-family="'Times New Roman', Georgia, serif" font-size="5" text-anchor="start" fill="#5d4037" font-weight="bold">IX</text>
                                <text x="95" y="77" font-family="'Times New Roman', Georgia, serif" font-size="5" text-anchor="end" fill="#5d4037" font-weight="bold">III</text>
                                <defs>
                                    <radialGradient id="gold-gradient" cx="30%" cy="30%" r="70%">
                                        <stop offset="0%" stop-color="#fff" stop-opacity="0.5" />
                                        <stop offset="30%" stop-color="#ffd700" />
                                        <stop offset="70%" stop-color="#d4af37" />
                                        <stop offset="100%" stop-color="#aa7c11" />
                                    </radialGradient>
                                </defs>
                            </svg>
                        </div>

                        <!-- Right: Signature -->
                        <div class="signature-container">
                            <p class="sig-title">{{ $t('hrm_dashboard.signed') }}</p>
                            <div class="sig-line"></div>
                            <p class="sig-name">{{ $t('hrm_dashboard.the_management_team') }}</p>
                            <p class="sig-role">{{ $t('hrm_dashboard.birthday_coordinator') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </a-card>
</template>

<script>
import { ref, defineComponent, watch, computed } from "vue";
import { useI18n } from "vue-i18n";
import common from "../../../composable/common";

export default defineComponent({
    props: ["data"],
    setup(props) {
        const { t } = useI18n();
        const { appSetting } = common();
        const todayBirthdays = ref([]);

        watch(
            () => props.data,
            (newVal) => {
                // Guard: props.data may be null/undefined during initial render
                // or when the parent clears the value.
                todayBirthdays.value = newVal?.todayBirthdays ?? [];
            },
            { immediate: true }
        );

        const formattedTodayDate = computed(() => {
            return new Date().toLocaleDateString("en-US", {
                month: "long",
                day: "numeric",
                year: "numeric"
            });
        });

        const getWishMessage = (persons) => {
            const baseMessage = t("hrm_dashboard.birthday_wish_message");
            if (persons && persons.length > 0) {
                const names = persons.map((p) => p.name).join(" & ");
                return baseMessage.replace("##NAME##", names);
            }
            return baseMessage;
        };

        const getConfettiStyle = (n) => {
            const colors = ["#d4af37", "#aa7c11", "#8a6d3b", "#ffd700", "#ebdcb9", "#795548"];
            return {
                left: `${n * 6.6}%`,
                animationDelay: `${n * 0.2}s`,
                backgroundColor: colors[n % colors.length],
                width: `${5 + (n % 5)}px`,
                height: `${5 + (n % 5)}px`
            };
        };

        return { todayBirthdays, formattedTodayDate, getConfettiStyle, getWishMessage };
    }
});
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Great+Vibes&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap");

.birthday-wish-card {
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.certificate-frame {
    position: relative;
    background-color: #f7ebd3;
    background-image:
        radial-gradient(circle at 50% 50%, #fbf5e6 0%, #f3e6c9 70%, #e2d1ad 100%),
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix type='matrix' values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.05 0'/%3E%3C/filter%3E%3Crect width='100' height='100' filter='url(%23noise)' opacity='0.15'/%3E%3C/svg%3E");
    padding: 24px;
    border: 10px solid #5d4037;
    border-radius: 12px;
    box-shadow: inset 0 0 40px rgba(93, 64, 55, 0.15);
    min-height: 500px;
    box-sizing: border-box;
    overflow: hidden;
}

.border-inlay {
    border: 2px double #a1887f;
    border-radius: 6px;
    padding: 24px 16px;
    min-height: 430px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.corner-flourish {
    position: absolute;
    z-index: 2;
    pointer-events: none;
}

.corner-flourish.tl {
    top: 14px;
    left: 14px;
}

.corner-flourish.tr {
    top: 14px;
    right: 14px;
    transform: scaleX(-1);
}

.corner-flourish.br {
    bottom: 14px;
    right: 14px;
    transform: scale(-1);
}

.certificate-content {
    text-align: center;
    width: 100%;
}

.cert-title {
    font-family: "Cinzel", Georgia, serif;
    font-size: 30px;
    font-weight: 800;
    color: #4e342e;
    margin: 5px 0 2px 0;
    letter-spacing: 4px;
    text-transform: uppercase;
}

.cert-subtitle {
    font-family: "Playfair Display", Georgia, serif;
    font-style: italic;
    font-size: 15px;
    color: #5d4037;
    margin-bottom: 15px;
}

.recipient-container {
    margin-bottom: 12px;
}

.avatar-cameo {
    display: inline-block;
    position: relative;
    padding: 6px;
    background: radial-gradient(circle, #ffffff 40%, #d4af37 100%);
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(93, 64, 55, 0.25);
    margin-bottom: 8px;
}

.birthday-avatar {
    border: 2px solid #8d6e63;
}

.recipient-name {
    font-family: "Great Vibes", "Brush Script MT", cursive;
    font-size: 44px;
    color: #8d6e63;
    margin: 4px 0 0 0;
    line-height: 1.1;
}

.name-underline {
    width: 150px;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, #8d6e63 50%, transparent 100%);
    margin: 4px auto 8px auto;
}

.recipient-designation {
    font-family: "Playfair Display", Georgia, serif;
    font-size: 14px;
    font-weight: 600;
    color: #5d4037;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 2px;
}

.recipient-age {
    font-family: "Playfair Display", Georgia, serif;
    font-style: italic;
    font-size: 13px;
    color: #795548;
    margin-bottom: 8px;
}

.celebration-date {
    font-family: "Playfair Display", Georgia, serif;
    font-size: 13px;
    color: #5d4037;
    margin-bottom: 15px;
}

.wish-text-container {
    border-top: 1px solid rgba(141, 110, 99, 0.2);
    border-bottom: 1px solid rgba(141, 110, 99, 0.2);
    padding: 12px 10px;
    margin: 10px auto;
    max-width: 85%;
}

.wish-message {
    font-family: "Playfair Display", Georgia, serif;
    font-size: 14px;
    line-height: 1.8;
    color: #4e342e;
    white-space: pre-line;
    margin: 0;
    text-align: center;
}

.certificate-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 15px;
    padding: 0 10px;
}

.watch-container {
    width: 90px;
    height: 90px;
}

.pocket-watch-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(2px 3px 5px rgba(93, 64, 55, 0.2));
}

.signature-container {
    text-align: center;
    width: 180px;
}

.sig-title {
    font-family: "Playfair Display", Georgia, serif;
    font-size: 13px;
    font-style: italic;
    color: #5d4037;
    margin: 0 0 2px 0;
}

.sig-line {
    width: 100%;
    height: 1px;
    background-color: #8d6e63;
    margin: 2px auto 6px auto;
}

.sig-name {
    font-family: "Playfair Display", Georgia, serif;
    font-weight: 700;
    font-size: 13px;
    color: #4e342e;
    margin: 0;
}

.sig-role {
    font-family: "Playfair Display", Georgia, serif;
    font-size: 10px;
    color: #795548;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

/* Confetti Falling Animation */
.confetti {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.confetti-piece {
    position: absolute;
    top: -10px;
    border-radius: 2px;
    animation: confettiFall 4s ease-in-out infinite;
}

@keyframes confettiFall {
    0% {
        transform: translateY(-10px) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(500px) rotate(720deg);
        opacity: 0;
    }
}

.company-logo-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 10px;
    margin-bottom: 5px;
    width: 100%;
}

.company-logo {
    max-height: 48px;
    max-width: 180px;
    object-fit: contain;
}
</style>

