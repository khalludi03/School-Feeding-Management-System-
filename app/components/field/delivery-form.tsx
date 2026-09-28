import * as React from 'react';
import { SchoolCombobox } from '@/components/forms/school-combobox';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Loader2, UploadCloud, X } from 'lucide-react';
import { cn } from '@/lib/utils';

// Types
interface School {
    id: number;
    code: string;
    bangla_name: string;
    emis_code: string | null;
    demands: Record<string, number | null>;
}

interface Item {
    id: number;
    item_key: string;
    name: string;
}

interface Allocation {
    date: string;
    quantity: number;
}

interface Props {
    csrfToken: string;
    formAction: string;
    method: 'POST' | 'PUT';
    date: string;
    schools: School[];
    items: Item[];
    existingFor: number[];
    isEdit: boolean;
    oldInput: any;
    errors: any;
    existingPhotoUrl?: string | null;
    existingNotes?: string;
    existingVariance?: string;
    existingCorrectionReason?: string;
    existingChalanNumber?: string;
    existingChalanDate?: string;
    existingSchoolId?: string;
}

export function DeliveryForm(props: Props) {
    const { schools, items, oldInput, errors } = props;

    // Form State
    const [schoolId, setSchoolId] = React.useState<string>(
        oldInput?.school_id || props.existingSchoolId || ''
    );
    const [quantities, setQuantities] = React.useState<Record<string, string>>({});
    const [allocations, setAllocations] = React.useState<Record<string, Allocation[]>>({});
    
    // Photo State
    const [isCompressing, setIsCompressing] = React.useState(false);
    const [photoUrl, setPhotoUrl] = React.useState<string | null>(props.existingPhotoUrl || null);
    const [photoError, setPhotoError] = React.useState<string | null>(null);
    const fileInputRef = React.useRef<HTMLInputElement>(null);
    const hiddenFileRef = React.useRef<HTMLInputElement>(null);

    // Initialize from old input if exists
    React.useEffect(() => {
        const initQuantities: Record<string, string> = {};
        const initAllocations: Record<string, Allocation[]> = {};
        
        items.forEach(item => {
            initQuantities[item.id] = oldInput?.quantities?.[item.id] ?? '';
            
            const oldAllocs = oldInput?.allocations?.[item.id];
            if (oldAllocs && Array.isArray(oldAllocs)) {
                initAllocations[item.id] = oldAllocs.map((a: any) => ({
                    date: a.date || '',
                    quantity: Number(a.quantity) || 0
                }));
            } else if (oldAllocs && typeof oldAllocs === 'object') {
                 initAllocations[item.id] = Object.values(oldAllocs).map((a: any) => ({
                    date: a.date || '',
                    quantity: Number(a.quantity) || 0
                }));
            }
        });
        setQuantities(initQuantities);
        setAllocations(initAllocations);
    }, []);

    const selectedSchool = React.useMemo(() => 
        schools.find(s => s.id.toString() === schoolId),
    [schools, schoolId]);

    const isAlreadyEntered = selectedSchool && props.existingFor.includes(selectedSchool.id) && !props.isEdit;

    const handlePhotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setPhotoError(null);
        setIsCompressing(true);

        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                // Scale down if extremely large (e.g. > 1600px)
                const MAX_DIMENSION = 1600;
                if (width > MAX_DIMENSION || height > MAX_DIMENSION) {
                    const ratio = Math.min(MAX_DIMENSION / width, MAX_DIMENSION / height);
                    width *= ratio;
                    height *= ratio;
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx?.drawImage(img, 0, 0, width, height);

                // Start at 85% quality
                let quality = 0.85;
                let dataUrl = canvas.toDataURL('image/jpeg', quality);

                // Binary search or loop to compress to < 4MB
                const targetSize = 4 * 1024 * 1024; // 4MB
                while (dataUrl.length > targetSize && quality > 0.1) {
                    quality -= 0.1;
                    dataUrl = canvas.toDataURL('image/jpeg', quality);
                }

                // If still too large
                if (dataUrl.length > targetSize) {
                    setPhotoError('Image is still too large even after compression.');
                    setIsCompressing(false);
                    return;
                }

                // Convert to blob and set input
                fetch(dataUrl)
                    .then(res => res.blob())
                    .then(blob => {
                        const compressedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });
                        
                        try {
                            // Assign to hidden input using DataTransfer
                            const dt = new DataTransfer();
                            dt.items.add(compressedFile);
                            if (hiddenFileRef.current) {
                                hiddenFileRef.current.files = dt.files;
                            }
                            setPhotoUrl(dataUrl);
                        } catch (err) {
                            console.error('DataTransfer not supported, falling back to base64');
                            // Fallback: put base64 into a hidden text input
                            const b64Input = document.getElementById('chalan_photo_base64') as HTMLInputElement;
                            if (b64Input) b64Input.value = dataUrl;
                            setPhotoUrl(dataUrl);
                        }
                        
                        setIsCompressing(false);
                    });
            };
            img.src = event.target?.result as string;
        };
        reader.readAsDataURL(file);
    };

    const removePhoto = () => {
        setPhotoUrl(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
        if (hiddenFileRef.current) hiddenFileRef.current.value = '';
        const b64Input = document.getElementById('chalan_photo_base64') as HTMLInputElement;
        if (b64Input) b64Input.value = '';
    };

    const addAllocation = (itemId: number) => {
        setAllocations(prev => ({
            ...prev,
            [itemId]: [...(prev[itemId] || []), { date: props.date, quantity: 0 }]
        }));
    };

    const removeAllocation = (itemId: number, index: number) => {
        setAllocations(prev => {
            const next = [...(prev[itemId] || [])];
            next.splice(index, 1);
            return { ...prev, [itemId]: next };
        });
    };

    return (
        <form method="POST" action={props.formAction} encType="multipart/form-data" className="space-y-6">
            <input type="hidden" name="_token" value={props.csrfToken} />
            {props.method === 'PUT' && <input type="hidden" name="_method" value="PUT" />}

            <div className="card-glass rounded-2xl p-5 shadow-sm space-y-5">
                <SchoolCombobox 
                    name="school_id" 
                    schools={schools} 
                    defaultValue={schoolId} 
                    error={errors.school_id?.[0]}
                    onChange={setSchoolId} 
                />

                {isAlreadyEntered && (
                    <div className="rounded-lg bg-blue-500/10 p-3 text-sm text-blue-600 dark:text-blue-400 border border-blue-500/20">
                        This school already has an entry today. You can add another chalan below.
                    </div>
                )}

                {selectedSchool && (
                    <div className="space-y-4 pt-2 border-t border-border/50">
                        <div className="grid gap-4 sm:grid-cols-2">
                            {items.map(item => {
                                const demand = selectedSchool.demands[item.item_key];
                                const currentQ = parseInt(quantities[item.id] || '0', 10);
                                const diff = currentQ - (demand || 0);
                                const hasDemand = demand !== null && demand !== undefined;

                                return (
                                    <div key={item.id} className="rounded-xl border border-border p-4 bg-card/50">
                                        <div className="flex items-center justify-between mb-2">
                                            <Label htmlFor={`q-${item.id}`} className="font-semibold">{item.name} received</Label>
                                            {hasDemand && quantities[item.id] && (
                                                <span className={cn(
                                                    "text-xs font-semibold px-2 py-0.5 rounded-full",
                                                    diff > 0 ? "bg-amber-500/15 text-amber-600 dark:text-amber-400" :
                                                    diff < 0 ? "bg-rose-500/15 text-rose-600 dark:text-rose-400" :
                                                    "bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"
                                                )}>
                                                    {diff > 0 ? `+${diff} excess` : diff < 0 ? `${diff} shortfall` : 'Matches demand'}
                                                </span>
                                            )}
                                        </div>
                                        <Input
                                            id={`q-${item.id}`}
                                            name={`quantities[${item.id}]`}
                                            type="number"
                                            inputMode="numeric"
                                            min="0"
                                            step="1"
                                            value={quantities[item.id] || ''}
                                            onChange={e => setQuantities(prev => ({...prev, [item.id]: e.target.value}))}
                                            className={errors[`quantities.${item.id}`] ? "border-destructive" : ""}
                                        />
                                        
                                        <div className="mt-3">
                                            {(allocations[item.id] || []).map((alloc, idx) => (
                                                <div key={idx} className="flex flex-wrap items-end gap-2 mt-2">
                                                    <div>
                                                        <Input type="date" name={`allocations[${item.id}][${idx}][date]`} defaultValue={alloc.date} />
                                                    </div>
                                                    <div>
                                                        <Input type="number" name={`allocations[${item.id}][${idx}][quantity]`} defaultValue={alloc.quantity.toString()} className="w-24" min="0" step="1" />
                                                    </div>
                                                    <Button type="button" variant="ghost" size="sm" onClick={() => removeAllocation(item.id, idx)} className="text-destructive hover:bg-destructive/10">Remove</Button>
                                                </div>
                                            ))}
                                            <button type="button" onClick={() => addAllocation(item.id)} className="mt-2 text-sm font-semibold text-primary hover:underline">+ Add distribution date</button>
                                        </div>
                                    </div>
                                )
                            })}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 pt-2">
                            <div>
                                <Label htmlFor="chalan_number">Chalan number</Label>
                                <Input id="chalan_number" name="chalan_number" defaultValue={oldInput.chalan_number || props.existingChalanNumber} className="mt-1" />
                                {errors.chalan_number && <p className="text-destructive text-xs mt-1">{errors.chalan_number[0]}</p>}
                            </div>
                            <div>
                                <Label htmlFor="chalan_date">Chalan date</Label>
                                <Input id="chalan_date" name="chalan_date" type="date" defaultValue={oldInput.chalan_date || props.existingChalanDate} className="mt-1" />
                                {errors.chalan_date && <p className="text-destructive text-xs mt-1">{errors.chalan_date[0]}</p>}
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Chalan photo</Label>
                                
                                <div className="rounded-xl border border-dashed border-border p-4 flex flex-col items-center justify-center bg-card/30 relative overflow-hidden min-h-[160px]">
                                    {isCompressing ? (
                                        <div className="flex flex-col items-center justify-center text-muted-foreground">
                                            <Loader2 className="h-8 w-8 animate-spin mb-2" />
                                            <span className="text-sm">Compressing photo...</span>
                                        </div>
                                    ) : photoUrl ? (
                                        <div className="absolute inset-0 w-full h-full">
                                            <img src={photoUrl} alt="Preview" className="w-full h-full object-cover" />
                                            <div className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                                <div className="flex gap-2">
                                                    <Button type="button" variant="secondary" size="sm" onClick={() => fileInputRef.current?.click()}>Retake</Button>
                                                    <Button type="button" variant="destructive" size="sm" onClick={removePhoto}><X className="h-4 w-4" /></Button>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="text-center" onClick={() => fileInputRef.current?.click()}>
                                            <UploadCloud className="h-8 w-8 text-muted-foreground mx-auto mb-2 cursor-pointer" />
                                            <span className="text-sm text-muted-foreground font-medium cursor-pointer">Tap to upload photo</span>
                                            <p className="text-xs text-muted-foreground/70 mt-1 max-w-[200px]">Compressed automatically before upload (max 4MB)</p>
                                        </div>
                                    )}
                                    <input 
                                        type="file" 
                                        accept="image/*" 
                                        capture="environment" 
                                        className="hidden" 
                                        ref={fileInputRef} 
                                        onChange={handlePhotoChange} 
                                    />
                                    {/* The actual file input sent to server */}
                                    <input type="file" name="chalan_photo" className="hidden" ref={hiddenFileRef} />
                                    {/* Fallback for old Safari */}
                                    <input type="hidden" name="chalan_photo_base64" id="chalan_photo_base64" />
                                </div>
                                {photoError && <p className="text-destructive text-xs mt-1">{photoError}</p>}
                                {errors.chalan_photo && <p className="text-destructive text-xs mt-1">{errors.chalan_photo[0]}</p>}
                            </div>
                            
                            <div>
                                <Label htmlFor="notes">Notes</Label>
                                <Input id="notes" name="notes" defaultValue={oldInput.notes || props.existingNotes} className="mt-1" />
                                {errors.notes && <p className="text-destructive text-xs mt-1">{errors.notes[0]}</p>}
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="variance_explanation">Variance explanation</Label>
                            <Input id="variance_explanation" name="variance_explanation" defaultValue={oldInput.variance_explanation || props.existingVariance} className="mt-1" />
                            <p className="text-xs text-muted-foreground mt-1">Required if allocated quantities differ from demand.</p>
                            {errors.variance_explanation && <p className="text-destructive text-xs mt-1">{errors.variance_explanation[0]}</p>}
                        </div>

                        {props.isEdit && (
                            <div>
                                <Label htmlFor="correction_reason">Reason for correction</Label>
                                <Input id="correction_reason" name="correction_reason" defaultValue={oldInput.correction_reason || props.existingCorrectionReason} className="mt-1" />
                                <p className="text-xs text-muted-foreground mt-1">Required for every correction.</p>
                                {errors.correction_reason && <p className="text-destructive text-xs mt-1">{errors.correction_reason[0]}</p>}
                            </div>
                        )}

                        <div className="pt-4 border-t border-border flex items-center gap-4">
                            <Button type="submit" disabled={isCompressing}>
                                {props.isEdit ? 'Save correction' : 'Record delivery'}
                            </Button>
                            {props.existingPhotoUrl && !photoUrl && (
                                <span className="text-sm text-muted-foreground">Existing photo will be kept.</span>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </form>
    );
}
