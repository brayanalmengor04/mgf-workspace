#!/usr/bin/env python3
"""Build a Lottie JSON image-sequence from the budget-scan pen video."""

from __future__ import annotations

import json
import math
import os
from pathlib import Path

import imageio.v3 as iio
from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
VIDEO = ROOT / "Pero_sin_los_numeros_porfa_deb.mp4"
OUT_DIR = ROOT / "public" / "lottie" / "budget-scan-pen"
FRAMES_DIR = OUT_DIR / "frames"
LOTTIE_FILE = OUT_DIR / "budget-scan-pen.json"

FPS = 24
WIDTH = 360
HEIGHT = 202
STEP = 4  # every 4th frame (~6 fps playback within 24fps comp)


def ease(t: float) -> float:
    return 0.5 - math.cos(math.pi * t) / 2


def build_lottie(frame_files: list[str], total_source_frames: int) -> dict:
    hold = STEP
    op = (len(frame_files) - 1) * hold + hold

    assets = []
    layers = []

    for index, filename in enumerate(frame_files):
        asset_id = f"frame_{index:03d}"
        assets.append(
            {
                "id": asset_id,
                "w": WIDTH,
                "h": HEIGHT,
                "u": "frames/",
                "p": filename,
                "e": 0,
            }
        )

        opacity_keys: list[dict] = []
        start = index * hold
        end = start + hold

        if index == 0:
            opacity_keys.append({"t": 0, "s": [100]})
        else:
            opacity_keys.append({"t": start - 1, "s": [0]})
            opacity_keys.append({"t": start, "s": [100]})

        if index < len(frame_files) - 1:
            opacity_keys.append({"t": end, "s": [0], "h": 1})

        layers.append(
            {
                "ddd": 0,
                "ind": index + 2,
                "ty": 2,
                "nm": asset_id,
                "refId": asset_id,
                "sr": 1,
                "ks": {
                    "o": {"a": 1, "k": opacity_keys},
                    "r": {"a": 0, "k": 0},
                    "p": {"a": 0, "k": [WIDTH / 2, HEIGHT / 2, 0]},
                    "a": {"a": 0, "k": [WIDTH / 2, HEIGHT / 2, 0]},
                    "s": {"a": 0, "k": [100, 100, 100]},
                },
                "ao": 0,
                "ip": 0,
                "op": op,
                "st": 0,
                "bm": 0,
            }
        )

    # Subtle float on the whole composition via a null isn't needed; add AI sparkle vector on top.
    sparkle = {
        "ddd": 0,
        "ind": len(frame_files) + 2,
        "ty": 4,
        "nm": "AI Sparkle",
        "sr": 1,
        "ks": {
            "o": {
                "a": 1,
                "k": [
                    {"t": 0, "s": [35]},
                    {"t": 60, "s": [90]},
                    {"t": 120, "s": [40]},
                    {"t": 180, "s": [85]},
                    {"t": op, "s": [35]},
                ],
            },
            "r": {"a": 1, "k": [{"t": 0, "s": [0]}, {"t": op, "s": [45]}]},
            "p": {"a": 0, "k": [WIDTH - 28, HEIGHT - 24, 0]},
            "a": {"a": 0, "k": [0, 0, 0]},
            "s": {
                "a": 1,
                "k": [
                    {"t": 0, "s": [80, 80, 100]},
                    {"t": 60, "s": [110, 110, 100]},
                    {"t": 120, "s": [85, 85, 100]},
                    {"t": 180, "s": [115, 115, 100]},
                    {"t": op, "s": [80, 80, 100]},
                ],
            },
        },
        "ao": 0,
        "shapes": [
            {
                "ty": "gr",
                "it": [
                    {
                        "ty": "sr",
                        "sy": 1,
                        "d": 1,
                        "pt": {"a": 0, "k": 4},
                        "p": {"a": 0, "k": [0, 0]},
                        "r": {"a": 0, "k": 0},
                        "ir": {"a": 0, "k": 3},
                        "is": {"a": 0, "k": 0},
                        "or": {"a": 0, "k": 10},
                        "os": {"a": 0, "k": 0},
                    },
                    {"ty": "fl", "c": {"a": 0, "k": [0.27, 0.32, 0.96, 1]}, "o": {"a": 0, "k": 100}},
                    {"ty": "tr", "p": {"a": 0, "k": [0, 0]}, "a": {"a": 0, "k": [0, 0]}, "s": {"a": 0, "k": [100, 100]}, "r": {"a": 0, "k": 0}, "o": {"a": 0, "k": 100}},
                ],
                "nm": "Star",
                "bm": 0,
            }
        ],
        "ip": 0,
        "op": op,
        "st": 0,
        "bm": 0,
    }

    layers.append(sparkle)

    return {
        "v": "5.7.4",
        "fr": FPS,
        "ip": 0,
        "op": op,
        "w": WIDTH,
        "h": HEIGHT,
        "nm": "MGF Budget Scan Pen",
        "ddd": 0,
        "assets": assets,
        "layers": list(reversed(layers)),
        "markers": [],
    }


def main() -> None:
    if not VIDEO.exists():
        raise SystemExit(f"Video not found: {VIDEO}")

    FRAMES_DIR.mkdir(parents=True, exist_ok=True)
    for old in FRAMES_DIR.glob("*.webp"):
        old.unlink()

    frames = iio.imread(VIDEO, index=...)
    total = len(frames)
    selected = list(range(0, total, STEP))
    frame_files: list[str] = []

    for seq, index in enumerate(selected):
        image = Image.fromarray(frames[index])
        image = image.resize((WIDTH, HEIGHT), Image.Resampling.LANCZOS)
        filename = f"f_{seq:03d}.webp"
        out_path = FRAMES_DIR / filename
        image.save(out_path, format="WEBP", quality=82, method=6)
        frame_files.append(filename)

    lottie = build_lottie(frame_files, total)
    LOTTIE_FILE.write_text(json.dumps(lottie, separators=(",", ":")), encoding="utf-8")

    total_kb = sum(p.stat().st_size for p in FRAMES_DIR.glob("*.webp")) / 1024
    print(f"Wrote {LOTTIE_FILE} ({LOTTIE_FILE.stat().st_size / 1024:.1f} KB)")
    print(f"Frames: {len(frame_files)} in {FRAMES_DIR} ({total_kb:.1f} KB)")


if __name__ == "__main__":
    main()
