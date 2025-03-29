#!/bin/bash

# Input parameters
FASTA_FILE="$1"
RESULTS_DIR="$2"

# Check if input file exists
if [ ! -f "$FASTA_FILE" ]; then
    echo "FASTA file not found!"
    exit 1
fi

# Output file for motif search results
OUTPUT_FILE="$RESULTS_DIR/patmatmotifs_results.txt"

# Run EMBOSS patmatmotifs to scan sequences for motifs
patmatmotifs -sequence "$FASTA_FILE" -outfile "$OUTPUT_FILE" -full Y

# Check if patmatmotifs ran successfully
if [ ! -s "$OUTPUT_FILE" ]; then
    echo "ERROR: No motifs found or patmatmotifs failed to run correctly." > "$OUTPUT_FILE"
    exit 1
fi

# Success message
echo "Motif search completed. Results saved to $OUTPUT_FILE."
exit 0

