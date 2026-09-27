import React from 'react';

export default function LoadingSkeleton({ rows = 5, cols = 6 }) {
    return (
        <div className="w-full animate-pulse space-y-4 p-4">
            <div className="h-9 bg-neutral-200/70 rounded-lg w-full mb-4" />
            {Array.from({ length: rows }).map((_, i) => (
                <div key={i} className="flex gap-4 items-center">
                    {Array.from({ length: cols }).map((_, j) => (
                        <div
                            key={j}
                            className={`h-6 bg-neutral-200/60 rounded ${
                                j === 0 ? 'w-24' : j === 1 ? 'w-48' : 'flex-1'
                            }`}
                        />
                    ))}
                </div>
            ))}
        </div>
    );
}
