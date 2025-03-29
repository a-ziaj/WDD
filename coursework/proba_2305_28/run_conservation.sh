#!/bin/bash

# Input parameters
FASTA_FILE="$1"
ALIGN_FILE="$2"
RESULTS_DIR="$3"
WINDOW_SIZE="${4:-4}"

# Create results directory
mkdir -p "$RESULTS_DIR"

# 1. Run Clustal Omega alignment if not already done
if [ ! -f "$ALIGN_FILE" ]; then
    clustalo -i "$FASTA_FILE" -o "$ALIGN_FILE" --force --threads=1 --outfmt=clustal
    if [ ! -s "$ALIGN_FILE" ]; then
        echo "ERROR: Clustal Omega alignment failed"
        exit 1
    fi
fi

# 2. Run Plotcon analysis
plotcon -sequences "$ALIGN_FILE" -graph png -winsize "$WINDOW_SIZE" -goutfile "$RESULTS_DIR/plotcon" 2>&1

# Handle plotcon's output file naming
if [ -f "$RESULTS_DIR/plotcon.1.png" ]; then
    mv "$RESULTS_DIR/plotcon.1.png" "$RESULTS_DIR/plotcon.png"
fi

# 3. Run Shannon Entropy analysis
python3 - <<EOF
import numpy as np
import matplotlib.pyplot as plt
from Bio import AlignIO
from collections import Counter
import json

def calculate_entropy(column):
    counts = Counter(column)
    total = len(column)
    return -sum((count/total) * np.log2(count/total) for count in counts.values())

# Read alignment
alignment = AlignIO.read("$ALIGN_FILE", "clustal")
entropy = []

# Calculate entropy for each position
for i in range(alignment.get_alignment_length()):
    column = str(alignment[:, i]).replace('-', '')  # Ignore gaps
    if column:  # Only calculate if not all gaps
        entropy.append(calculate_entropy(column))
    else:
        entropy.append(0)  # All gaps = 0 entropy

# Create static plot
plt.figure(figsize=(12, 6))
plt.plot(entropy, color='blue')
plt.axhline(y=np.mean(entropy), color='r', linestyle='--', label='Mean entropy')
plt.title('Shannon Entropy (gap positions excluded)')
plt.xlabel('Position')
plt.ylabel('Entropy (bits)')
plt.legend()
plt.tight_layout()
plt.savefig("$RESULTS_DIR/entropy.png")

# Create interactive plot data
plot_data = {
    "data": [{
        "y": entropy,
        "type": "line",
        "name": "Entropy",
        "line": {"color": "blue"}
    }],
    "layout": {
        "title": "Shannon Entropy Analysis",
        "xaxis": {"title": "Position"},
        "yaxis": {"title": "Entropy (bits)"},
        "shapes": [{
            "type": "line",
            "x0": 0,
            "x1": len(entropy),
            "y0": np.mean(entropy),
            "y1": np.mean(entropy),
            "line": {"color": "red", "dash": "dash"}
        }]
    }
}

with open("$RESULTS_DIR/entropy.json", 'w') as f:
    json.dump(plot_data, f)

# Generate simplified alignment view
with open("$RESULTS_DIR/alignment.txt", 'w') as f:
    for record in alignment:
        f.write(f">{record.id}\n")
        f.write(f"{str(record.seq)}\n\n")
EOF

exit 0
