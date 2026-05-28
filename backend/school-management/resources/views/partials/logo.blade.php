<div class="logo-section">
    <div class="logo-container">
        <!-- Engranajes -->
        <div class="gear gear-outer"></div>
        <div class="gear gear-middle"></div>
        <div class="gear gear-inner"></div>

        <!-- Nopal -->
        <div class="nopal">
            <div class="nopal-leaf leaf-left"></div>
            <div class="nopal-leaf leaf-right"></div>
            <div class="nopal-body"></div>
            <div class="nopal-spines"></div>
        </div>

        <!-- Fruto -->
        <div class="fruit"></div>
    </div>

    <div class="school-info">
        <div class="school-name">CBTA No. 71 TLALNEPANTLA</div>
        <div class="school-campus">MORELOS</div>
    </div>
</div>

<style>
    :root {
        --color-gold: #ffb347;
        --logo-size: 100px;
        --logo-size-mobile: 70px;
        --logo-size-small: 60px;
    }

    .logo-section {
        display: flex;
        align-items: center;
        gap: clamp(12px, 3vw, 20px);
        flex-wrap: wrap;
    }

    .logo-container {
        width: min(var(--logo-size), 15vw, 100px);
        height: min(var(--logo-size), 15vw, 100px);
        min-width: 60px;
        min-height: 60px;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 30%, #1f5e4c, #0a2e2a 90%);
        border: clamp(3px, 0.8vw, 5px) solid #bfd8d1;
        box-shadow: 0 clamp(4px, 1vw, 6px) clamp(8px, 2vw, 14px) rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        flex-shrink: 0;
        aspect-ratio: 1/1;
    }

    .gear {
        position: absolute;
        border-radius: 50%;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    .gear-outer {
        width: 90%;
        height: 90%;
        border: clamp(1.5px, 0.3vw, 2px) dashed rgba(255,255,255,0.6);
    }

    .gear-middle {
        width: 76%;
        height: 76%;
        border: clamp(1.5px, 0.3vw, 2px) solid rgba(215,230,223,0.9);
    }

    .gear-inner {
        width: 62%;
        height: 62%;
        background: rgba(15,55,50,0.8);
        border: clamp(1.5px, 0.3vw, 2px) solid #aac9bf;
    }

    .nopal {
        position: absolute;
        width: 26%;
        height: 34%;
        bottom: 30%;
        left: 50%;
        transform: translateX(-50%);
    }

    .nopal-leaf {
        position: absolute;
        width: 46%;
        height: 53%;
        background: linear-gradient(180deg,#49a98d,#2d7f6b);
        border: clamp(1.5px, 0.3vw, 2px) solid #cfe6df;
        border-radius: 10px 10px 6px 10px;
    }

    .nopal-leaf.leaf-left {
        left: -30%;
        bottom: 24%;
        border-right: none;
    }

    .nopal-leaf.leaf-right {
        right: -30%;
        bottom: 30%;
        border-left: none;
        border-radius: 10px 10px 10px 6px;
    }

    .nopal-body {
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 70%;
        height: 100%;
        background: linear-gradient(180deg,#5ec2a3,#2d7f6b);
        border: clamp(1.5px, 0.3vw, 2px) solid #e2f1ec;
        border-radius: 12px 12px 8px 8px;
        box-shadow: inset 0 0 clamp(4px, 0.8vw, 6px) rgba(0,0,0,0.25),
        0 clamp(1.5px, 0.3vw, 2px) clamp(2px, 0.4vw, 3px) rgba(0,0,0,0.35);
    }

    .nopal-spines {
        position: absolute;
        bottom: 9%;
        left: 50%;
        transform: translateX(-50%);
        width: 8%;
        height: 82%;
        background: rgba(255,255,255,0.35);
        border-radius: 2px;
        box-shadow: -5px 0 0 rgba(255,255,255,0.18),
        5px 0 0 rgba(255,255,255,0.18);
    }

    .fruit {
        position: absolute;
        top: 35%;
        right: 32%;
        width: 6%;
        height: 6%;
        min-width: 4px;
        min-height: 4px;
        background: var(--color-gold);
        border-radius: 50%;
        box-shadow: 0 0 0 clamp(1.5px, 0.3vw, 2px) rgba(255,180,70,0.3);
    }

    .school-info {
        flex: 1;
        min-width: 200px;
    }

    .school-name {
        font-size: clamp(16px, 4vw, 20px);
        font-weight: 700;
        letter-spacing: clamp(0.5px, 0.1vw, 1px);
        color: #e3fff5;
        text-shadow: 1px 1px 0 #0a2e2a;
        margin: 0 0 clamp(2px, 0.5vw, 4px);
        line-height: 1.3;
        word-break: break-word;
    }

    .school-campus {
        font-size: clamp(13px, 3vw, 16px);
        font-weight: 600;
        color: #d4f0e6;
        letter-spacing: clamp(1px, 0.2vw, 2px);
        text-transform: uppercase;
        border-top: 1px solid #6f9e92;
        display: inline-block;
        padding-top: clamp(3px, 0.6vw, 5px);
        margin: 0;
    }

    @media (max-width: 480px) {
        .logo-container {
            width: var(--logo-size-mobile);
            height: var(--logo-size-mobile);
        }
    }

    @media (max-width: 360px) {
        .logo-section {
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 8px;
        }

        .logo-container {
            width: var(--logo-size-small);
            height: var(--logo-size-small);
            margin-bottom: 0;
        }

        .school-info {
            text-align: center;
            min-width: auto;
        }

        .school-name {
            font-size: 16px;
        }

        .school-campus {
            font-size: 12px;
        }
    }

    @media (min-width: 768px) and (max-width: 1024px) {
        .logo-container {
            width: 90px;
            height: 90px;
        }

        .school-name {
            font-size: 19px;
        }
    }

    @media (min-width: 1200px) {
        .logo-container {
            width: 110px;
            height: 110px;
        }
    }
</style>
